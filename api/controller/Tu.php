<?php


namespace app\api\controller;


use app\common\controller\Api;
use think\Config;
use think\Db;

class Tu extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function loadlist(){
        $lotteryType = $this->request->post('lotteryType', 1);
        if($lotteryType==''){
            $lotteryType = 1;
        }
        $page = $this->request->post('page', 1);
        $color = $this->request->post('color', 1);
        $year = $this->request->post('year', 2023);
        $ps = 10;
        if($color==1){
            $dir = Config::get('tuku.ctdir');
        }else{
            $dir = Config::get('tuku.hbdir');
        }
        if($lotteryType == 2){
            //澳门
            $list = Db::name('tu_aomen')->field('pictureName,pictureTypeId,pictureUrl')->where(['color'=>$color])->order(Db::raw('lastdown*1 desc'))->limit($ps)->page($page)->select();
            if($color==1){
                $url = '/aomen/'.$year.'/col/';
            }else{
                $url = '/aomen/'.$year.'/black/';
            }
            $qishu = Db::name('dd')->where(['key'=>'amqs'])->value('value');
        }elseif($lotteryType == 1){
            //香港
            $list = Db::name('tu_xianggang')->field('pictureName,pictureTypeId,pictureUrl')->where(['color'=>$color])->order(Db::raw('lastdown*1 desc'))->limit($ps)->page($page)->select();
            if($color==1){
                $url = '/hk/'.$year.'/col/';
            }else{
                $url = '/hk/'.$year.'/black/';
            }
            $qishu = Db::name('dd')->where(['key'=>'xgqs'])->value('value');
        }elseif($lotteryType == 3){
            //台湾
            $list = Db::name('tu_taiwan')->field('pictureName,pictureTypeId,pictureUrl')->where(['color'=>$color])->order(Db::raw('lastdown*1 desc'))->limit($ps)->page($page)->select();
            if($color==1){
                $url = '/taiwan/'.$year.'/col/';
            }else{
                $url = '/taiwan/'.$year.'/black/';
            }
            $qishu = Db::name('dd')->where(['key'=>'twqs'])->value('value');
        }elseif($lotteryType == 4){
            //新加坡
            $list = Db::name('tu_xinjiapo')->field('pictureName,pictureTypeId,pictureUrl')->where(['color'=>$color])->order(Db::raw('lastdown*1 desc'))->limit($ps)->page($page)->select();
            if($color==1){
                $url = '/xinjiapo/'.$year.'/col/';
            }else{
                $url = '/xinjiapo/'.$year.'/black/';
            }
            $qishu = Db::name('dd')->where(['key'=>'xjpqs'])->value('value');
        }
        foreach ($list as &$item){
            $item['pic'] = getCdnUrl($url .$qishu . '/' .$item['pictureUrl']);
        }
        $this->success('ok', $list);
    }


    public function initPicData(){
        $picId = $this->request->post('picId', 0);
        $lotteryType = $this->request->post('lotteryType', 1);
        //获取档期期数
        $dd = Db::name('dd')->column('value', 'key');
        if($lotteryType == 2){
            $latestQi = $dd['amqs'];
            $pic = Db::name('tu_aomen')->where(['pictureTypeId'=>$picId])->find();
        }elseif($lotteryType == 1){
            $latestQi = $dd['xgqs'];
            $pic = Db::name('tu_xianggang')->where(['pictureTypeId'=>$picId])->find();
        }elseif($lotteryType == 3){
            $latestQi = $dd['twqs'];
            $pic = Db::name('tu_taiwan')->where(['pictureTypeId'=>$picId])->find();
        }elseif($lotteryType == 4){
            $latestQi = $dd['xjpqs'];
            $pic = Db::name('tu_xinjiapo')->where(['pictureTypeId'=>$picId])->find();
        }
        if(!$pic){
            $this->error('图片不存在');
        }
        $data = $pic;
        $data['qishu'] = [];
        for ($i = $latestQi; $i>0; $i--){
            $vo = [];
            $vo['name'] = '第'.$i.'期';
            $vo['qi'] = $i*1;
            $vo['pictureTypeId'] = $picId;
            $data['qishu'][] = $vo;
        }
        $this->success('ok', $data);
    }

    public function detail()
    {
        $picId = $this->request->post('picId', 0);
        $lotteryType = $this->request->post('lotteryType', 1);
        $color = $this->request->post('color', 1);
        $year = $this->request->post('year', 2023);
        $qishu = $this->request->post('qi', 0);
        if($color==1){
            $dir = Config::get('tuku.ctdir');
        }else{
            $dir = Config::get('tuku.hbdir');
        }
        if($lotteryType == 2){
            //澳门
            $pic = Db::name('tu_aomen')->where(['pictureTypeId'=>$picId])->find();
            $color = $pic['color'];
            if($color==1){
                $url = Config::get('tuku.amct');
                $local = '/aomen/'.$year.'/col/';
            }else{
                $url = Config::get('tuku.amhb');
                $local = '/aomen/'.$year.'/black/';
            }
            if($qishu==0){
                $qishu = Db::name('dd')->where(['key'=>'amqs'])->value('value');
            }
        }elseif($lotteryType == 1){
            //香港
            $pic = Db::name('tu_xianggang')->where(['pictureTypeId'=>$picId])->find();
            $color = $pic['color'];
            if($color==1){
                $url = Config::get('tuku.xgct');
                $local = '/hk/'.$year.'/col/';
            }else{
                $url = Config::get('tuku.xghb');
                $local = '/hk/'.$year.'/black/';
            }
            if($qishu==0){
                $qishu = Db::name('dd')->where(['key'=>'xgqs'])->value('value');
            }
        }elseif($lotteryType == 3){
            //台湾
            $pic = Db::name('tu_taiwan')->where(['pictureTypeId'=>$picId])->find();
            $color = $pic['color'];
            if($color==1){
                $url = Config::get('tuku.twct');
                $local = '/taiwan/'.$year.'/col/';
            }else{
                $url = Config::get('tuku.twhb');
                $local = '/taiwan/'.$year.'/black/';
            }
            if($qishu==0){
                $qishu = Db::name('dd')->where(['key'=>'twqs'])->value('value');
            }
        }elseif($lotteryType == 4){
            //新加坡
            $pic = Db::name('tu_xinjiapo')->where(['pictureTypeId'=>$picId])->find();
            $color = $pic['color'];
            if($color==1){
                $url = Config::get('tuku.xjpct');
                $local = '/xinjiapo/'.$year.'/col/';
            }else{
                $url = Config::get('tuku.xjphb');
                $local = '/xinjiapo/'.$year.'/black/';
            }
            if($qishu==0){
                $qishu = Db::name('dd')->where(['key'=>'xjpqs'])->value('value');
            }
        }
        $qishu = intval($qishu);
        $pic['picurl'] = $url .($year==date('Y')?'':$year.'/'). $dir .$qishu . '/' .$pic['pictureUrl'];

        //判断图片是否下载
        $path = ROOT_PATH. DS.'public'.DS. $local . DS .$qishu;
        if(!file_exists($path.DS.$pic['pictureUrl'])){
            //立即下载图片
            if(!is_dir($path)){
                mkdir($path,0777,true);
            }
            fileDow($pic['picurl'], $path);
        }

        $pic['url'] = getCdnUrl($local.'/'.$qishu.'/'.$pic['pictureUrl']);
        $pic['qi'] = $qishu;
        //获取生肖投票
        $volist = Db::name('shengxiao s')
            ->field('s.sx, s.id as sx_id, ifnull(v.num, 0)  num')
            ->join('tu_vote v', 'v.sx_id = s.id and v.lotteryType='.$lotteryType.' and v.year='.$year.' and v.pictureTypeId='.$picId.' and v.qi='.$qishu, 'left')
            ->select();
        $sumvote = 0;
        foreach ($volist as $item){
            $sumvote += $item['num'];
        }
        foreach ($volist as &$item){
            if($sumvote>0){
                $item['pro'] = number_format($item['num'] / $sumvote * 100)*1;
            }else{
                $item['pro'] = 0;
            }
        }
        $pic['votelist'] = $volist;

        $likebar = Db::name('tulikebar')->where(['lotteryType'=>$lotteryType, 'year'=>$year, 'qi'=>$qishu, 'pictureTypeId'=>$picId])->find();
        if(!$likebar){
            $did = Db::name('tulikebar')->insertGetId(['lotteryType'=>$lotteryType, 'year'=>$year, 'qi'=>$qishu, 'pictureTypeId'=>$picId]);
            $likebar['collectcount'] = 0;
            $likebar['zancount'] = 0;
            $likebar['clickCount'] = 0;
            $likebar['commentCount'] = 0;
            $likebar['id'] = $did;
        }
        //是否点赞
        $likebar['iszan'] = false;
        //是否点赞
        $likebar['isshoucang'] = false;
        if($this->auth->isLogin()){
            $user = $this->auth->getUserinfo();
            if($this->auth->isLogin()){
                $zan = Db::name('zan')->where(['user_id'=>$user['id'],'area'=>3, 'data_id'=>$likebar['id']])->find();
                if($zan){
                    $likebar['iszan'] = true;
                }
            }
            if($this->auth->isLogin()){
                $sc = Db::name('shoucang')->where(['user_id'=>$user['id'],'area'=>2, 'data_id'=>$likebar['id']])->find();
                if($sc){
                    $likebar['isshoucang'] = true;
                }
            }
        }
        $pic['likebar'] = $likebar;
        Db::name('tulikebar')->where(['id'=>$likebar['id']])->setInc('clickCount');
        //图解标题
        $pic['tjtitle'] = '第'.$year.$pic['qi'].$pic['pictureName'].'图解';
        $this->success('ok', $pic);
    }

    public function next()
    {
        $picId = $this->request->post('picId', 0);
        $lotteryType = $this->request->post('lotteryType', 1);
        $act = $this->request->post('act', 'next');
        if($lotteryType == 2){
            //澳门
            if($act=='next'){
                $pic = Db::name('tu_aomen')->where(['pictureTypeId'=>['gt',$picId]])->order('pictureTypeId asc')->find();
            }else{
                $pic = Db::name('tu_aomen')->where(['pictureTypeId'=>['lt',$picId]])->order('pictureTypeId desc')->find();
            }
        }elseif($lotteryType == 1){
            //香港
            if($act=='next'){
                $pic = Db::name('tu_xianggang')->where(['pictureTypeId'=>['gt',$picId]])->order('pictureTypeId asc')->find();
            }else{
                $pic = Db::name('tu_xianggang')->where(['pictureTypeId'=>['lt',$picId]])->order('pictureTypeId desc')->find();
            }
        }elseif($lotteryType == 3){
            //台湾
            if($act=='next'){
                $pic = Db::name('tu_taiwan')->where(['pictureTypeId'=>['gt',$picId]])->order('pictureTypeId asc')->find();
            }else{
                $pic = Db::name('tu_taiwan')->where(['pictureTypeId'=>['lt',$picId]])->order('pictureTypeId desc')->find();
            }
        }elseif($lotteryType == 4){
            //新加坡
            if($act=='next'){
                $pic = Db::name('tu_xinjiapo')->where(['pictureTypeId'=>['gt',$picId]])->order('pictureTypeId asc')->find();
            }else{
                $pic = Db::name('tu_xinjiapo')->where(['pictureTypeId'=>['lt',$picId]])->order('pictureTypeId desc')->find();
            }
        }
        if(!$pic){
            $this->error('没有了');
        }
        $this->success('ij', $pic);
    }


    public function vote(){
        $picId = $this->request->post('picId', 0);
        $lotteryType = $this->request->post('lotteryType', 1);
        $year = $this->request->post('year', 2023);
        $qishu = $this->request->post('qi', 0);
        $sx_id = $this->request->post('sx_id', 0);
        $where['pictureTypeId'] = $picId;
        $where['year'] = $year;
        $where['qi'] = $qishu;
        $where['lotteryType'] = $lotteryType;
        $where['sx_id'] = $sx_id;
        $has = Db::name('tu_vote')->where($where)->find();
        if(!$has){
            Db::name('tu_vote')->insertGetId($where);
        }else{
            Db::name('tu_vote')->where(['id'=>$has['id']])->setInc('num');
        }
        $this->success('投票成功');
    }

    //索引列表
    public function indexedList(){
        $color = $this->request->post('color', 1);
        $lotteryType = $this->request->post('lotteryType', 1);
        $keyword = $this->request->param('keyword', '');
        $where['color'] = $color;
        if($keyword!=''){
            $where['pictureName'] = ['like', '%'.$keyword.'%'];
        }
        //获取档期期数
        $dd = Db::name('dd')->column('value', 'key');
        if($lotteryType == 2){
            $piclist = Db::name('tu_aomen')->where($where)->order('letter asc')->select();
            $qishu = $dd['amqs'];
        }elseif($lotteryType == 1){
            $piclist = Db::name('tu_xianggang')->where($where)->order('letter asc')->select();
            $qishu = $dd['xgqs'];
        }elseif($lotteryType == 3){
            $piclist = Db::name('tu_taiwan')->where($where)->order('letter asc')->select();
            $qishu = $dd['twqs'];
        }elseif($lotteryType == 4){
            $piclist = Db::name('tu_xinjiapo')->where($where)->order('letter asc')->select();
            $qishu = $dd['xjpqs'];
        }
        $templist = [];
        foreach ($piclist as $item){
            if(in_array($item['letter'], ['1','2','3','4','5','6','《'])){
                continue;
            }
            $templist[$item['letter']]['letter'] = $item['letter'];
            $templist[$item['letter']]['data'][] = '第'.$qishu.'期:'.$item['pictureName'];
            $templist[$item['letter']]['typeId'][] = $item['pictureTypeId'];
        }
        $list = [];
        foreach ($templist as $item){
            $list[] = $item;
        }
        $this->success('ok', $list);
    }



}