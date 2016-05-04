<?php

use App\Erp\Organizations\Organization;
use App\Erp\Organizations\Warehouse;
use App\Erp\Sales\Order;
use App\Erp\Sales\Repositories\OrderRepository;
use App\Listeners\ItemReserveListener;
use App\Listeners\ItemStockCreateListener;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OrderRepositoryTest extends TestCase
{
    use MakeOrderTrait, ApiTestTrait, DatabaseTransactions;

    /**
     * @var OrderRepository
     */
    protected $orderRepo;

    public function setUp()
    {
        parent::setUp();
        $this->orderRepo = App::make(OrderRepository::class);
    }

    /*
        public function testCreateOrder()
        {
            $order = $this->fakeOrderData();
            $createdOrder = $this->orderRepo->create($order);
            $createdOrder = $createdOrder->toArray();
            $this->assertArrayHasKey('id', $createdOrder);
            $this->assertNotNull($createdOrder['id'], 'Created Order must have id specified');
            $this->assertNotNull(Order::find($createdOrder['id']), 'Order with given id must be in DB');
            $this->assertModelData($order, $createdOrder);
        }
    
    
        public function testReadOrder()
        {
            $order = $this->makeOrder();
            $dbOrder = $this->orderRepo->find($order->id);
            $dbOrder = $dbOrder->toArray();
            $this->assertModelData($order->toArray(), $dbOrder);
        }
    
    
        public function testUpdateOrder()
        {
            $order = $this->makeOrder();
            $fakeOrder = $this->fakeOrderData();
            $updatedOrder = $this->orderRepo->update($fakeOrder, $order->id);
            $this->assertModelData($fakeOrder, $updatedOrder->toArray());
            $dbOrder = $this->orderRepo->find($order->id);
            $this->assertModelData($fakeOrder, $dbOrder->toArray());
        }
    
    
        public function testDeleteOrder()
        {
            $order = $this->makeOrder();
            $resp = $this->orderRepo->delete($order->id);
            $this->assertTrue($resp);
            $this->assertNull(Order::find($order->id), 'Order should not exist in DB');
        }*/


    public function testCreateOrder()
    {
        $order = factory(Order::class)->create();

        $this->assertInstanceOf(Warehouse::class, $order->warehouse);
        $this->assertInstanceOf(Organization::class, $order->organization);

        $this->assertEquals(0, $order->weight);



    }

    /**
     *
     */
    public function testCreateOrderWithWarehouse()
    {


        $warehouse = factory(Warehouse::class)->create();
        $currentOrganization = $warehouse->organization;



        $order = factory(Order::class)->create(['warehouse_id'=>$warehouse->id]);

        $this->assertInstanceOf(Warehouse::class, $order->warehouse);
        $this->assertInstanceOf(Organization::class, $order->organization);

        //когда устанавливаем склад для заказа, организация автоматоически должна устанавливаться из него
        $this->assertEquals($warehouse->id, $order->warehouse->id);
        $this->assertEquals($currentOrganization->id, $order->organization->id);



    }


    public function testChangeOrderWarehouse()
    {


        $warehouse = factory(Warehouse::class)->create();
        $currentOrganization = $warehouse->organization;
        $warehouse2 = factory(Warehouse::class)->create();


        $order = factory(Order::class)->create(['warehouse_id'=>$warehouse->id]);

        $this->setExpectedException(\Exception::class);


        $order->warehouse()->associate($warehouse2);
        $order->save();


    }


    public function testCreateOrderItem()
    {

        $order = factory(Order::class)->create();
        $warehouse = $order->warehouse;
        $product = factory(\App\Erp\Catalog\Product::class)->create(['weight'=>10]);

        $stock = factory(\App\Erp\Stocks\Stock::class)->create(['warehouse_id'=>$warehouse->id,
        'product_id'=>$product->id
        ]);

        $orderItem = factory(\App\Erp\Sales\OrderItem::class)->make();

        $orderItem->document()->associate($order);
        $orderItem->product()->associate($product);

        $orderItem->price = 1000;
        $orderItem->qty = 5;



      //  $this->expectsEvents(ItemReserveListener::class);
       // $this->expectsEvents(ItemStockCreateListener::class);

        //если total не установлен, то считаем автоматом
        $orderItem->save();

        //$this->assertEquals($orderItem->total, 5000);

        //для StockItem сток явно не установлен, но он должен автоматом установиться на основании
        //склада и товара
        $this->assertEquals($orderItem->stock->id, $stock->id);



        $orderItem->total = 4000;
        $orderItem->price = 1000;
        $orderItem->qty = 3;
        $orderItem->save();
        $this->assertEquals($orderItem->total, 4000);





    }
}