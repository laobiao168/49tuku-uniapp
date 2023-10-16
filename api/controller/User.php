<?php

namespace app\api\controller;

use app\api\logic\UserLogic;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Db;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third', 'userInfo'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param string $code     验证码
     */
    public function register()
    {
        $username = $this->request->post('account');
        $password = $this->request->post('password');
        $mobile = $this->request->post('mobile', '');
        $code = $this->request->post('code');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            //$this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            //$this->error(__('Captcha is incorrect'));
        }
        $ret = $this->auth->register($username, $password, $mobile, []);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    public function info(){
        $user = $this->auth->getUserinfo();
        $this->success('ok', $user);
    }

    public function userInfo(){
        $uid = $this->request->param('id', 0);
        if($this->auth->isLogin()) {
            $user = $this->auth->getUserinfo();
        }else{
            $user = [];
        }
        $this->success('ok', UserLogic::getUserInfo($uid, $user['id']??0));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->post('username');
        $nickname = $this->request->post('nickname');
        $bio = $this->request->post('bio');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @param string $email   邮箱
     * @param string $captcha 验证码
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @param string $platform 平台名称
     * @param string $code     Code码
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd()
    {
        $type = $this->request->post("type");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    public function toggleFocus(){
        $user = $this->auth->getUserinfo();
        $user_id = $this->request->param('user_id', 0);
        $has = Db::name('fans')->where(['user_id'=>$user_id, 'fans_id'=>$user['id']])->find();
        if($has){
            //取消
            Db::name('fans')->where(['user_id'=>$user_id, 'fans_id'=>$user['id']])->delete();
            Db::name('user')->where(['id'=>$user_id])->setDec('num_fans');
            Db::name('user')->where(['id'=>$user['id']])->setDec('num_focus');
            $this->success('ok', ['txt'=>'关注']);
        }else{
            //加关注
            Db::name('fans')->insertGetId(['user_id'=>$user_id, 'fans_id'=>$user['id'], 'addtime'=>time()]);
            Db::name('user')->where(['id'=>$user_id])->setInc('num_fans');
            Db::name('user')->where(['id'=>$user['id']])->setInc('num_focus');
            $this->success('ok', ['txt'=>'已关注']);
        }
    }

    public function mycollectlist(){
        $user = $this->auth->getUserinfo();
        $lotteryType = $this->request->post('lotteryType', 1);
        if($lotteryType==''){
            $lotteryType = 1;
        }
        $page = $this->request->post('page', 1);
        $type = $this->request->post('type', 1);
        $tab = $this->request->post('tab', 1);
        $keyword = $this->request->post('keyword', '');
        $ps = 10;
        $dir1 = Config::get('tuku.ctdir');
        $dir2 = Config::get('tuku.hbdir');

        if(in_array($tab, [1,2,4])){
            $where = [];
            $where['s.user_id'] = $user['id'];
            $where['a.type'] = $type;
            if($lotteryType){
                $where['a.lotteryType'] = $lotteryType;
            }
            if($keyword!=''){
                $where['a.title'] = ['like', '%'. trim($keyword).'%'];
            }
            $orderby = 'a.id desc';
            $list = Db::name('shoucang s')
                ->join('bbs a', 'a.id = s.data_id', 'left')
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
                }
            }
        }elseif($tab==3){
            //文章
            $where = [];
            $where['s.area'] = 1;
            $where['s.user_id'] = $user['id'];
            if($keyword!=''){
                $where['a.title'] = ['like', '%'. trim($keyword).'%'];
            }
            if($lotteryType){
                $where['t.lotteryType'] = $lotteryType;
            }
            $list = Db::name('shoucang s')
                ->join('caiji_articles a', 'a.id = s.data_id', 'left')
                ->join('fa_caiji_articletype t', 't.articleTypeId = a.articleTypeId', 'left')
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
        }else{
            $where = [];
            if($lotteryType == 2){
                //澳门
                $where['s.user_id'] = $user['id'];
                $list = Db::name('shoucang s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_aomen t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'amqs'])->value('value');
            }elseif($lotteryType == 1){
                //香港
                $where['s.user_id'] = $user['id'];
                $list = Db::name('shoucang s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_xianggang t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'xgqs'])->value('value');
            }elseif($lotteryType == 3){
                //台湾
                $where['s.user_id'] = $user['id'];
                $list = Db::name('shoucang s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_taiwan t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'twqs'])->value('value');
            }elseif($lotteryType == 4){
                //新加坡
                $where['s.user_id'] = $user['id'];
                $list = Db::name('shoucang s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_xinjiapo t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'xjpqs'])->value('value');
            }
            foreach ($list as &$item){
                if($item['color']==1){
                    $item['pic'] = Config::get('tuku.amct').($item['year']==date('Y')?'':$item['year'].'/'). $dir1 .$qishu . '/' .$item['pictureUrl'];
                }else{
                    $item['pic'] = Config::get('tuku.amhb').($item['year']==date('Y')?'':$item['year'].'/'). $dir2 .$qishu . '/' .$item['pictureUrl'];
                }
            }
        }

        $this->success('ok', $list);
    }


    public function mylovelist(){
        $user = $this->auth->getUserinfo();
        $lotteryType = $this->request->post('lotteryType', 1);
        if($lotteryType==''){
            $lotteryType = 1;
        }
        $page = $this->request->post('page', 1);
        $type = $this->request->post('type', 1);
        $tab = $this->request->post('tab', 1);
        $keyword = $this->request->post('keyword', '');
        $ps = 10;
        $dir1 = Config::get('tuku.ctdir');
        $dir2 = Config::get('tuku.hbdir');

        if(in_array($tab, [1,2,4])){
            $where = [];
            $where['s.user_id'] = $user['id'];
            $where['a.type'] = $type;
            if($lotteryType){
                $where['a.lotteryType'] = $lotteryType;
            }
            if($keyword!=''){
                $where['a.title'] = ['like', '%'. trim($keyword).'%'];
            }
            $where['s.area'] = 4;
            $orderby = 'a.id desc';
            $list = Db::name('zan s')
                ->join('bbs a', 'a.id = s.data_id', 'left')
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
                }
            }
        }elseif($tab==3){
            //文章
            $where = [];
            $where['s.area'] = 2;
            $where['s.user_id'] = $user['id'];
            if($keyword!=''){
                $where['a.title'] = ['like', '%'. trim($keyword).'%'];
            }
            if($lotteryType){
                $where['t.lotteryType'] = $lotteryType;
            }
            $list = Db::name('zan s')
                ->join('caiji_articles a', 'a.id = s.data_id', 'left')
                ->join('fa_caiji_articletype t', 't.articleTypeId = a.articleTypeId', 'left')
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
        }else{
            $where = [];
            $where['s.area'] = 3;
            if($lotteryType == 2){
                //澳门
                $where['s.user_id'] = $user['id'];
                $list = Db::name('zan s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_aomen t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year,s.addtime')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'amqs'])->value('value');
            }elseif($lotteryType == 1){
                //香港
                $where['s.user_id'] = $user['id'];
                $list = Db::name('zan s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_xianggang t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year,s.addtime')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'xgqs'])->value('value');
            }elseif($lotteryType == 3){
                //台湾
                $where['s.user_id'] = $user['id'];
                $list = Db::name('zan s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_taiwan t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year,s.addtime')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'twqs'])->value('value');
            }elseif($lotteryType == 4){
                //新加坡
                $where['s.user_id'] = $user['id'];
                $list = Db::name('zan s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_xinjiapo t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year,s.addtime')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'xjpqs'])->value('value');
            }
            foreach ($list as &$item){
                if($item['color']==1){
                    $item['pic'] = Config::get('tuku.amct').($item['year']==date('Y')?'':$item['year'].'/'). $dir1 .$qishu . '/' .$item['pictureUrl'];
                }else{
                    $item['pic'] = Config::get('tuku.amhb').($item['year']==date('Y')?'':$item['year'].'/'). $dir2 .$qishu . '/' .$item['pictureUrl'];
                }
                $item['avatar'] = $item['pic'];
                $item['title'] = $item['pictureName'];
                $item['username'] = $item['pictureName'];
            }
        }

        $this->success('ok', $list);
    }


    public function mycommentlist(){
        $user = $this->auth->getUserinfo();
        $lotteryType = $this->request->post('lotteryType', 1);
        if($lotteryType==''){
            $lotteryType = 1;
        }
        $page = $this->request->post('page', 1);
        $type = $this->request->post('type', 1);
        $tab = $this->request->post('tab', 1);
        $keyword = $this->request->post('keyword', '');
        $ps = 10;
        $dir1 = Config::get('tuku.ctdir');
        $dir2 = Config::get('tuku.hbdir');

        if(in_array($tab, [1,2,4])){
            $where = [];
            $where['s.user_id'] = $user['id'];
            $where['a.type'] = $type;
            if($lotteryType){
                $where['a.lotteryType'] = $lotteryType;
            }
            if($keyword!=''){
                $where['a.title'] = ['like', '%'. trim($keyword).'%'];
            }
            $where['s.area'] = 3;
            $orderby = 'a.id desc';
            $list = Db::name('comments s')
                ->join('bbs a', 'a.id = s.data_id', 'left')
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
                }
            }
        }elseif($tab==3){
            //文章
            $where = [];
            $where['s.area'] = 1;
            $where['s.user_id'] = $user['id'];
            if($keyword!=''){
                $where['a.title'] = ['like', '%'. trim($keyword).'%'];
            }
            if($lotteryType){
                $where['t.lotteryType'] = $lotteryType;
            }
            $list = Db::name('comments s')
                ->join('caiji_articles a', 'a.id = s.data_id', 'left')
                ->join('fa_caiji_articletype t', 't.articleTypeId = a.articleTypeId', 'left')
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
        }else{
            $where = [];
            $where['s.area'] = 2;
            if($lotteryType == 2){
                //澳门
                $where['s.user_id'] = $user['id'];
                $list = Db::name('comments s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_aomen t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year,s.addtime')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'amqs'])->value('value');
            }elseif($lotteryType == 1){
                //香港
                $where['s.user_id'] = $user['id'];
                $list = Db::name('comments s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_xianggang t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year,s.addtime')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'xgqs'])->value('value');
            }elseif($lotteryType == 3){
                //台湾
                $where['s.user_id'] = $user['id'];
                $list = Db::name('comments s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_taiwan t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year,s.addtime')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'twqs'])->value('value');
            }elseif($lotteryType == 4){
                //新加坡
                $where['s.user_id'] = $user['id'];
                $list = Db::name('comments s')
                    ->join('tulikebar d', 'd.id = s.data_id', 'left')
                    ->join('tu_xinjiapo t', 't.pictureTypeId = d.pictureTypeId', 'left')
                    ->field('t.pictureName,t.pictureTypeId,t.pictureUrl,t.color,d.year,s.addtime')
                    ->where($where)->whereNotNull('t.pictureTypeId')
                    ->order('s.id desc')->limit($ps)->page($page)->select();
                $qishu = Db::name('dd')->where(['key'=>'xjpqs'])->value('value');
            }
            foreach ($list as &$item){
                if($item['color']==1){
                    $item['pic'] = Config::get('tuku.amct').($item['year']==date('Y')?'':$item['year'].'/'). $dir1 .$qishu . '/' .$item['pictureUrl'];
                }else{
                    $item['pic'] = Config::get('tuku.amhb').($item['year']==date('Y')?'':$item['year'].'/'). $dir2 .$qishu . '/' .$item['pictureUrl'];
                }
                $item['avatar'] = $item['pic'];
                $item['title'] = $item['pictureName'];
                $item['username'] = $item['pictureName'];
            }
        }

        $this->success('ok', $list);
    }


    public function mycare(){
        $page = $this->request->post('page', 1);
        $user = $this->auth->getUserinfo();
        $where['f.fans_id'] = $user['id'];
        $list = Db::name('fans f')
                ->join('user u', 'f.user_id = u.id', 'left')->field('u.username, u.avatar, u.id')
            ->where($where)->whereNotNull('u.id')
            ->limit(10)->page($page)->order('f.id desc')->select();
        foreach ($list as &$item){
            if($item['avatar']==''){
                $item['avatar'] = letter_avatar($item['username']);
            }
        }
        $this->success('ok', $list);
    }

    public function myfans(){
        $page = $this->request->post('page', 1);
        $user = $this->auth->getUserinfo();
        $where['f.user_id'] = $user['id'];
        $list = Db::name('fans f')
            ->join('user u', 'f.fans_id = u.id', 'left')->field('u.username, u.avatar, u.id')
            ->where($where)->whereNotNull('u.id')
            ->limit(10)->page($page)->order('f.id desc')->select();
        foreach ($list as &$item){
            if($item['avatar']==''){
                $item['avatar'] = letter_avatar($item['username']);
            }
        }
        $this->success('ok', $list);
    }

}
