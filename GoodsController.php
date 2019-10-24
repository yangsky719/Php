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
        $goodsname = $this->request->getData('商品');//请考虑在接口传递上使用商品id，以避免重名商品的潜在问题
        //获取商品订购数量
        $quantity = $this->request->getData('数量');

        try {
            //
            $conn = ConnectionManager::get('default');

            //若无必要，则尽量不用select *
            $results = $conn->execute('select * from traning_goods where name=:name and stock >= :stock',
                ['name' => $goodsname, 'stock' => $quantity])
                ->fetchAll('assoc');

            //return是动词，一般仅用在function上，原则上，变量应全部使用名词形式
            $result = [];//若php版本大于5.4，则建议数组声明使用字面量形式，更简短

            if (count($results) == 1) {
                //可以订购
                //事务开始
                $OrderNum = null;//驼峰风格不宜与下划线同用

                $conn->begin();

                //扣掉对应商品库存
                $conn->update('traning_goods',
                    ['stock' => ($results[0]['stock'] - $quantity)],
                    ['name' => $goodsname]);

                //生成订单记录
                $Order_Num = date("YmdHis");
                $conn->insert('traning_order',
                    [
                        'order_num' => $Order_Num,
                        'goods_id' => $results[0]['id'],
                        'quantity' => $quantity,
                        'created_at' => 0,//字段含义为订单创建时间，应当赋以time()值
                        'updated_at' => 0,//建议assoc数组多行书写时，最后一行始终加逗号，是一种对git管理更友好的写法
                    ]

                );

                //提交事务
                $conn->commit();

                $result = array(
                    'error_code' => 0,
                    'data' => array(
                        'order' => $Order_Num
                    )
                );


            } else {
                //库存不足
                $result = array(
                    'error_code' => 1,
                    'data' => array(),
                    'msg' => "库存不足"

                );
            }

            $this->set('returns', $result);
        } catch (Exception $e) {
            throw new InternalErrorException('错误:' . $e->getMessage());
        }


    }

    public function index()
    {
        $goods = ['商品A' => '商品A', '商品B' => '商品B', '商品C' => '商品C'];

        $this->set('goods', $goods);

    }

}

?>
