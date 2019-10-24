<?php
namespace App\Controller;


use App\Controller\AppController;
use Cake\Datasource\ConnectionManager;
use App\Form\ContactForm;

class GoodsController extends AppController
{
    public function preOrder()
    {
      //获取商品名称
      $goodsname=$this->request->getData('商品');
      //获取商品订购数量
      $quantity =$this->request->getData('数量');
    
      try{
            //
            $conn =ConnectionManager::get('default');
            
            $results = $conn->execute('select * from traning_goods where name=:name and stock >= :stock',
                            ['name'=>$goodsname,'stock'=>$quantity])
                            ->fetchAll('assoc');
            

            

            $returns=array();

            if(count($results)==1)
            {
                //可以订购
                //事务开始
                $Order_Num=null;

                $conn->begin();

                //扣掉对应商品库存
                $conn->update('traning_goods', 
                             ['stock' => ($results[0]['stock']- $quantity) ],
                             ['name' => $goodsname]);

                //生成订单记录
                $Order_Num=date("YmdHis");
                $conn->insert('traning_order',
                             [
                                    'order_num' => $Order_Num,
                                    'goods_id' => $results[0]['id'],
                                    'quantity' => $quantity,
                                    'created_at' => 0,
                                    'updated_at' => 0
                             ]

                );

                //提交事务
                $conn->commit();

                $returns = array(
                    'error_code' => 0,
                    'data' => array(
                        'order' => $Order_Num
                    )
                );
                


                
            }else{
                //库存不足
                $returns = array(
                    'error_code' => 1,
                    'data' => array(
                        
                    ),
                    'msg' =>"库存不足"

                );
            }

            $this->set('returns',$returns);
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


