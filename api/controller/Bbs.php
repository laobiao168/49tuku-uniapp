<?php


namespace app\api\controller;


use app\api\logic\UserLogic;
use app\common\controller\Api;
use think\Db;

class Bbs extends Api
{

    protected $noNeedLogin = ['index', 'detail'];
    protected $noNeedRight = ['*'];

    //发帖
    public function add(){
        $lotteryType = $this->request->post('lotteryType', 1);
        $type = $this->request->post('type', 1);
        $picTypeId = $this->request->post('picTypeId', 0);
        $title = $this->request->post('title', '');
        $content = $this->request->post('content', '');
        $qi = $this->request->post('qi', '');
        $pics = $this->request->post('pics', '');
        $user = $this->auth->getUserinfo();
        if($qi==''){
            $dd = Db::name('dd')->column('value', 'key');
            if($lotteryType == 2){
                $qi = $dd['amqs'];
            }elseif($lotteryType == 1){
                $qi = $dd['xgqs'];
            }elseif($lotteryType == 3){
                $qi = $dd['twqs'];
            }elseif($lotteryType == 4){
                $qi = $dd['xjpqs'];
            }
        }
        if($type==3 && $pics == ''){
            $this->error('请上传图片');
        }
        $data['type'] = $type;
        $data['lotteryType'] = $lotteryType;
        $data['title'] = $title;
        $data['content'] = $content;
        $data['picTypeId'] = $picTypeId;
        $data['qi'] = $qi;
        $data['addtime'] = time();
        $data['user_id'] = $user['id'];
        if($pics !=''){
            $picarr = explode(',', $pics);
            if($picarr[0]!=''){
                $data['pic'] = $picarr[0];
            }
            $data['piclist'] = $pics;
        }
        Db::name('bbs')->insertGetId($data);
        Db::name('user')->where(['id'=>$user['id']])->setInc('num_article');
        $this->success('发布成功');

    }



    public function index(){
        $lotteryType = $this->request->post('lotteryType', 0);
        $type = $this->request->post('type', 1);
        $page = $this->request->post('page', 1);
        $order = $this->request->post('order', 1);
        $userId = $this->request->post('userid', 0);
        $keyword = $this->request->post('keyword', '');
        $where['a.type'] = $type;
        $where['a.status'] = 1;
        if($lotteryType){
            $where['a.lotteryType'] = $lotteryType;
        }
        if($userId>0){
            $where['a.user_id'] = $userId;
        }
        if($keyword!=''){
            $where['a.title'] = ['like', '%'. trim($keyword).'%'];
        }
        $orderby = 'a.weight desc,a.id desc';
        if($order == 2){
            $orderby = 'a.jing desc,a.id desc';
        }elseif($order == 3){
            $orderby = 'a.zancount desc';
        }elseif($order == 4){
            $orderby = 'a.id desc';
        }
        $list = Db::name('bbs a')
            ->field('a.id, a.title, a.content,a.qi,a.pic, a.piclist, a.lotteryType,a.weight,a.jing, a.addtime, a.clickCount,collectcount,a.commentCount,a.zancount,a.user_id, u.username, u.avatar')
            ->join('user u', 'u.id = a.user_id', 'left')
            ->where($where)->limit(10)->page($page)->order($orderby)->select();
        foreach ($list as &$item){
            if($item['avatar']==''){
                $item['avatar'] = letter_avatar($item['username']);
            }
            $item['descp'] = getDescriptionFromContent($item['content'], 50);
            $item['images'] = [];
            if($item['piclist']!=''){
                $item['images'] = explode(',', $item['piclist']);
                foreach ($item['images'] as &$img){
                    $img = getCdnUrl($img);
                }
            }
            $item['pic'] = getCdnUrl($item['pic']);
        }
        $this->success('ok', $list);
    }


    public function detail(){
        $lid = $this->request->post('id', 0);
        $info = Db::name('bbs')->where(['id'=>$lid])->find();
        if($info){
            Db::name('bbs')->where(['id'=>$lid])->setInc('clickCount');
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
                    $zan = Db::name('zan')->where(['user_id'=>$user['id'],'area'=>4, 'data_id'=>$lid])->find();
                    if($zan){
                        $info['iszan'] = true;
                    }
                }
                if($this->auth->isLogin()){
                    $sc = Db::name('shoucang')->where(['user_id'=>$user['id'],'area'=>3, 'data_id'=>$lid])->find();
                    if($sc){
                        $info['isshoucang'] = true;
                    }
                }
            }
            //图片
            $info['images'] = [];
            if($info['piclist']!=''){
                $info['images'] = explode(',', $info['piclist']);
            }
            $this->success('ok', $info);
        }else{
            $this->error('数据不存在');
        }
    }


}