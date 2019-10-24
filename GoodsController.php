<?php
namespace App\Controller;


use App\Controller\AppController;
use Cake\Datasource\ConnectionManager;
use App\Form\ContactForm;

class GoodsController extends AppController
{
    public function preOrder()
    {
      try{
        //获取商品名称
        $goodsname_id=$this->request->getData('商品id');
        //获取商品订购数量
        $quantity =$this->request->getData('数量');
    
            $redis = new Redis();#伪代码
            $rand_hash = randStr();#伪代码，生成一个随机字符串，用于判断锁是否属于本线程
            $redis->setnx("OrderLock", $rand_hash);#redis锁，依赖于setnx命令的原子性；假设有其他线程正在加锁，此处会保持等待
            $redis->expire("OrderLock", 5);#！非常重要！兜底机制，释放锁，防止死锁问题
            $lock = $redis->get("OrderLock");
            if ($lock === $rand_hash){//若判断通过，则证明当前加锁成功
                #可以进行下单
                $redis->del("OrderLock");#若下单成功，则主动释放锁
            }
            //
            $conn =ConnectionManager::get('default');
            $conn->begin();
          
            $results = $conn->execute('select id,stock from traning_goods where name=:name and stock >= :stock' for update,
                            ['id'=>$goodsname_id,'stock'=>$quantity])
                            ->fetchAll('assoc');
            

            

            $result=[];

            if(count($results)==1)
            {
                //可以订购
                //事务开始
                $order_num=null;

              

                //扣掉对应商品库存
                $conn->update('traning_goods', 
                             ['stock' => ($results[0]['stock']- $quantity) ],
                             ['id' => $goodsname_id]);

                //生成订单记录
                $order_num=date("YmdHis");
                $conn->insert('traning_order',
                             [
                                    'order_num' => $order_num,
                                    'goods_id' => $results[0]['id'],
                                    'quantity' => $quantity,
                                    'created_at' => time(),
                                    'updated_at' => time(),
                             ]

                );

                //提交事务
                $conn->commit();

                $result = array(
                    'error_code' => 0,
                    'data' => array(
                        'order' => $order_num
                    )
                );
                


                
            }else{
                //库存不足
                $result = array(
                    'error_code' => 1,
                    'data' => array(
                        
                    ),
                    'msg' =>"库存不足"

                );
            }

            $this->set('result',$result);
    }
    catch(Exception $e)
    {
        throw new InternalErrorException('错误:'.$e->getMessage());
    }
    finally{
        $redis->del("OrderLock");#下单失败，也释放锁
    }


    
    }

   public function index()
   {
       $goods = [ '商品A'=>'商品A','商品B'=>'商品B','商品C'=>'商品C'];

       $this->set('goods',$goods);

   }

}
?>


