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
    
    
            //
            $conn =ConnectionManager::get('default');
            
            $results = $conn->execute('select id,stock from traning_goods where name=:name and stock >= :stock',
                            ['id'=>$goodsname_id,'stock'=>$quantity])
                            ->fetchAll('assoc');
            

            

            $result=[];

            if(count($results)==1)
            {
                //可以订购
                //事务开始
                $order_num=null;

                $conn->begin();

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


    
    }

   public function index()
   {
       $goods = [ '商品A'=>'商品A','商品B'=>'商品B','商品C'=>'商品C'];

       $this->set('goods',$goods);

   }

}
?>


