<?php

namespace app\api\controller;

use app\api\logic\IndexLogic;
use app\common\controller\Api;
use QL\QueryList;
use think\Config;
use think\Db;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['init', 'index', 'banner', 'commentlist', 'xunbao'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $this->success('请求成功');
    }

    public function init()
    {
        $site = Config::get("site");
        $data['name'] = $site['name'];
        $data['marquee'] = $site['marquee'];
        $data['logo'] = $site['logo'];
        //开奖时间
        $dd = Db::name('dd')->column('value', 'key');
        $data['openTime'] = [date('m月d日', $dd['amopentime']),date('m月d日', $dd['xgopentime']),date('m月d日', $dd['twopentime']),date('m月d日', $dd['xjpopentime'])];
        //banner
        $data['banner'] = Db::name('banner')->where(['position'=>1])->order('sort desc')->select();
        foreach ($data['banner'] as &$item){
            $item['image'] = getCdnUrl($item['image']);
        }
        $this->success('请求成功', $data);
    }

    public function banner(){
        $position = $this->request->post('position', 1);
        $data['banner'] = Db::name('banner')->where(['position'=>$position])->order('sort desc')->select();
        $this->success('ok', $data);
    }

    public function commentlist(){
        $data_id = $this->request->post('data_id', 0);
        $area = $this->request->post('area', 1);
        $user = [];
        if($this->auth->isLogin()){
            $user = $this->auth->getUserinfo();
        }
        $data = IndexLogic::getCommentsList($area, $data_id, $user);
        $this->success('ok', $data);
    }

    //发评论
    public function postcomment(){
        $data_id = $this->request->post('data_id', 0);
        $area = $this->request->post('area', 1);
        $parentId = $this->request->post('parentId', 0);
        $content = $this->request->post('content', '');
        $user = $this->auth->getUserinfo();
        $has = Db::name('comments')->where(['user_id'=>$user['id'], 'data_id'=>$data_id, 'area'=>$area, 'parentId'=>$parentId])->find();
        if($has){
            $this->error('已评论请勿重复提交');
        }
        Db::name('comments')->insertGetId(['user_id'=>$user['id'], 'data_id'=>$data_id, 'area'=>$area, 'content'=>$content, 'parentId'=>$parentId, 'addtime'=>time()]);
        if($parentId>0){
            Db::name('comments')->where(['id'=>$parentId])->setInc('replayNum');
        }
        $this->success('评论成功');

    }

    //点赞
    public function dianzan(){
        $data_id = $this->request->post('data_id', 0);
        $area = $this->request->post('area', 1);
        $user = $this->auth->getUserinfo();
        $has = Db::name('zan')->where(['user_id'=>$user['id'], 'data_id'=>$data_id, 'area'=>$area])->find();
        if($has){
            $this->error('已赞');
        }
        Db::name('zan')->insertGetId(['user_id'=>$user['id'], 'data_id'=>$data_id, 'area'=>$area, 'addtime'=>time()]);
        //更新统计
        if($area == 1){
            $row = Db::name('comments')->where(['id'=>$data_id])->find();
            Db::name('user')->where(['id'=>$row['user_id']])->setInc('num_huozan');
        }elseif($area==2){
            //资料大全
            $row = Db::name('caiji_articles')->where(['id'=>$data_id])->find();
            Db::name('caiji_articles')->where(['id'=>$data_id])->setInc('zancount');
            Db::name('user')->where(['id'=>$row['user_id']])->setInc('num_huozan');
        }elseif($area==3){
            //图库
            Db::name('tulikebar')->where(['id'=>$data_id])->setInc('zancount');
        }elseif($area==4){
            //论坛
            $row = Db::name('bbs')->where(['id'=>$data_id])->find();
            Db::name('bbs')->where(['id'=>$data_id])->setInc('zancount');
            Db::name('user')->where(['id'=>$row['user_id']])->setInc('num_huozan');
        }
        $this->success('点赞成功');

    }

    //收藏
    public function shoucang(){
        $data_id = $this->request->post('data_id', 0);
        $area = $this->request->post('area', 1);
        $user = $this->auth->getUserinfo();
        $has = Db::name('shoucang')->where(['user_id'=>$user['id'], 'data_id'=>$data_id, 'area'=>$area])->find();
        if($has){
            $this->error('已收藏');
        }
        Db::name('shoucang')->insertGetId(['user_id'=>$user['id'], 'data_id'=>$data_id, 'area'=>$area, 'addtime'=>time()]);
        //更新统计
        if($area == 1){
            //资料大全
            Db::name('caiji_articles')->where(['id'=>$data_id])->setInc('collectcount');
        }elseif($area==2){
            //图库
            Db::name('tulikebar')->where(['id'=>$data_id])->setInc('collectcount');
        }elseif($area==3){
            //论坛
            Db::name('bbs')->where(['id'=>$data_id])->setInc('collectcount');
        }
        $this->success('收藏成功');

    }

    public function xunbao(){
        $templist = Db::name('xunbao')->order('sort desc')->where(['status'=>1])->select();
        $data['urls'] = [];
        $data['pts'] = [];
        foreach ($templist as $item){
            if($item['area'] == 1){
                $data['urls'][] = $item;
            }else{
                $data['pts'][] = $item;
            }
        }
        $this->success('ok', $data);
    }

    public function usertop(){
        $user = $this->auth->getUserinfo();
        $user['pm'] = '暂无排名';
        $type = $this->request->param('type', 1); //1粉丝；2等级
        $list = Db::name('user')->field('id, username, avatar, num_fans')->order('num_fans desc')->limit(50)->select();
        $newdata = [];
        foreach ($list as $i=>$item){
            if($item['avatar']==''){
                $item['avatar'] = letter_avatar($item['username']);
            }
            $item['images'] = [];
            if($item['id'] == $user['id']){
                $user['pm'] = $i+1;
            }
            $item['mc'] = $i+1;
            $newdata[] = $item;
        }
        $data['my'] = $user;
        $data['list'] = $newdata;
        $this->success('ok', $data);
    }



}
