<?php
namespace app\api\logic;


use think\Cache;
use think\Db;

class IndexLogic
{

    public static function getHome(){
        $ck = 'index_getHome';
        if(Cache::has($ck)){
            return Cache::get($ck);
        }
        //2.banner
        $data['banner'] = Db::name('banner')->where(['position'=>1])->select();
        Cache::set($ck, $data);fuck(2);
        return $data;
    }


    public static function getCommentsList($area, $dataid, $user=[]){
        //获取置顶的评论
        $where['c.parentId']=0;
        $where['c.top'] = 1;
        $toplist = Db::name('comments c')
            ->field('c.id,c.content,c.likenum,c.addtime,c.user_id, c.parentId, u.username as nickName, u.avatar')
            ->join('user u', 'u.id = c.user_id', 'left')
            ->where($where)
            ->order('id desc')->select();
        //获取内容评论
        $where2['c.top'] = 0;
        $where2['c.data_id'] = $dataid;
        $where2['c.area'] = $area;
        $where2['c.status'] = 1;
        $rowlist = Db::name('comments c')
            ->field('c.id,c.content,c.likenum,c.addtime,c.user_id, c.parentId, u.username as nickName, u.avatar')
            ->join('user u', 'u.id = c.user_id', 'left')
            ->where($where2)
            ->order('id desc')->select();
        $commentList = array_merge($toplist, $rowlist);
        //点赞判断
        $zanids = [];
        if($user){
            $zanids = Db::name('zan')->where(['user_id'=>$user['id'], 'area'=>1])->column('data_id');
        }
        foreach ($commentList as &$item){
            $item['owner'] = false;
            $item['hasLike'] = false;
            if($user){
                if($item['user_id'] == $user['id']){
                    $item['owner'] = true;
                }
            }
            if(in_array($item['id'], $zanids)){
                $item['hasLike'] = true;
            }
            //处理头像
            if($item['avatar'] == ''){
                $item['avatarUrl'] = letter_avatar($item['nickName']);
            }else{
                $item['avatarUrl'] = getCdnUrl($item['avatar']);
            }
            $item['createTime'] = date('Y-m-d H:i:s', $item['addtime']);
        }
        $data['commentList'] = $commentList;
        $data['readNumer'] = 999;
        return $data;
    }


}