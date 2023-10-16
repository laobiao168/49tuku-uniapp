<?php


namespace app\api\controller;

use app\api\logic\CaijiLogic;
use app\api\logic\UserLogic;
use app\common\controller\Api;
use think\Db;

/**
 *综合资料
 */
class Corpus extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function articleType(){
        $lotteryType = $this->request->post('lotteryType', 1);
        $list = Db::name('caiji_articletype')->where(['lotteryType'=>$lotteryType])->order('sort desc')->select();
        $this->success('ok', $list);
    }

    public function articlelist(){
        $articleTypeId = $this->request->post('articleTypeId', 0);
        $page = $this->request->post('page', 1);
        $keyword = $this->request->post('keyword', '');
        $where = [];
        if($articleTypeId){
            $where['a.articleTypeId'] = $articleTypeId;
        }
        if($keyword!=''){
            $where['a.title'] = ['like', '%'. trim($keyword).'%'];
        }
        $list = Db::name('caiji_articles a')
            ->field('a.id, a.title, a.addtime, a.clickCount,collectcount,a.commentCount,a.zancount,a.user_id, u.username, u.avatar')
            ->join('user u', 'u.id = a.user_id', 'left')
            ->order('a.id desc')
            ->where($where)->limit(10)->page($page)->select();
        foreach ($list as &$item){
            if($item['avatar']==''){
                $item['avatar'] = letter_avatar($item['username']);
            }
            $item['images'] = [];
        }
        $this->success('ok', $list);
    }

    public function detail(){
        $lid = $this->request->post('id', 0);
        $info = Db::name('caiji_articles')->where(['id'=>$lid])->find();
        if($info){
            $info['images'] = [];
            Db::name('caiji_articles')->where(['id'=>$lid])->setInc('clickCount');

            if($this->auth->isLogin()) {
                $user = $this->auth->getUserinfo();
            }else{
                $user = [];
            }
            $info['user'] = UserLogic::getUserInfo($info['user_id'], $user['id']??0);
            if($info['user']['avatar']==''){
                $info['user']['avatar'] = letter_avatar($info['user']['username']);
            }
            //是否点赞
            $info['iszan'] = false;
            //是否点赞
            $info['isshoucang'] = false;
            if($this->auth->isLogin()){
                if($this->auth->isLogin()){
                    $zan = Db::name('zan')->where(['user_id'=>$user['id'],'area'=>2, 'data_id'=>$lid])->find();
                    if($zan){
                        $info['iszan'] = true;
                    }
                }
                if($this->auth->isLogin()){
                    $sc = Db::name('shoucang')->where(['user_id'=>$user['id'],'area'=>1, 'data_id'=>$lid])->find();
                    if($sc){
                        $info['isshoucang'] = true;
                    }
                }
            }
            $this->success('ok', $info);
        }
    }

}