<?php


namespace app\api\controller;


use app\api\logic\OpenLiveLogic;
use app\common\controller\Api;
use think\Db;

class Openlive extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index(){
        $lotteryType = $this->request->post('lotteryType', 1);
        $row = Db::name('caiji_kjxc')->where(['lotteryType'=>$lotteryType])->find();
        if($row){
            $row['data'] = json_decode($row['data']);
            $this->success('ok', $row);
        }else{
            $this->error('没有数据');
        }
    }

    public function videoList(){
        $lotteryType = $this->request->post('lotteryType', 1);
        $page = $this->request->post('page', 1);
        $year = $this->request->post('year', 2023);
        $data = OpenLiveLogic::getVideoList($lotteryType, $year, $page);
        $this->success('ok', $data);
    }

    public function detail(){
        $id = $this->request->post('id', '');
        $lotteryType = $this->request->post('lotteryType', 1);

    }
}