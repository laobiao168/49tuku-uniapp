<?php


namespace app\api\controller;


use app\common\controller\Api;
use think\Cache;

class History extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    public function index(){
        $lotteryType = $this->request->param('lotteryType', 1);
        $year = $this->request->param('year', 2023);
        $page = $this->request->param('page', 1);
        $url = 'https://h5.49217005.com:8443/unite49/h5/lottery/search?pageNum='.$page.'&lotteryType='.$lotteryType.'&year='.$year.'&sort=1';
        $ck = $page.'&lotteryType='.$lotteryType.'&year='.$year;
        if(Cache::has($ck)){
            $this->success('ok', json_decode(Cache::get($ck), true));

        }
        $res = curl_get_https($url);
        Cache::set($ck, $res, 600);
        $this->success('ok', json_decode($res, true));
    }
}