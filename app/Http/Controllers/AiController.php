<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Product;
use App\Http\Controllers\CurlController;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function createArray(array $list, array &$return = null)
    {
        $return[] = $list;
        if (count($list) > 2) {
            foreach ($list as $value) {
                $list1 = array_diff($list, (array) $value);
                $this->createArray($list1, $return);
            }
        }
        return $return;
    }
    public function getTrain($user_id)
    {
        $listProduct = DB::table('user_product')->where('user_id',$user_id)->select('product_id')->get();
        foreach ($listProduct as $value) {
            $list[] = $value->product_id;
        }
        $list = array_unique($list);
        foreach ($list as $value) {
           $listProduct = Product::whereIn('id',$list)->get();
        }
        foreach ($listProduct as $value) {
            $p [] = $value->category_id;
        }
        $p = array_unique($p);
        $p = array_slice($p, -4);
        $p = $this->createArray((array)$p);
        array_multisort($p,SORT_DESC);
        $users = null;
        foreach ($p as $val) {
            $users = DB::table('user_category_train');
            foreach ($val as $value) {
                $users->where('category', 'like', "%$value%");
            }
            $users = $users->limit(200)->get();
            if (count($users) > 100)
            {
                return [$users,$val];
            }
            unset($users);
        }
        return null;
    }

    public function index(Request $request)
    {
        set_time_limit(0);
        $user_id = '0084FDDDFD25C812292B673906484C8C';
        $getTrain = $this->getTrain($user_id);
        $users = $getTrain['0'];
        $p = $getTrain['1'];
        $samples = array();

        foreach ($users as $value) {
            $samples[] = array_diff( (array)explode(",", $value->category), $p);
        }
        $samples = array_filter($samples);
        $list = array();
        foreach($samples as $user){
            foreach ($user as $product) {
                if(empty($list[$product]))
                    $list[$product]= 0;
                $list[$product]++;
            }
        }
        arsort($list);
        dump($p);
        dump($list);
    }


    public function recommend(Request $request)
    {
        set_time_limit(0);
        $return['status'] = 400; 
        if(empty($request->user_id))
           return response()->json($return);

        $user_id = $request->user_id;
        $getTrain = $this->getTrain($user_id);
        $users = $getTrain['0'];
        $p = $getTrain['1'];
        $samples = array();

        foreach ($users as $value) {
            $samples[] = array_diff( (array)explode(",", $value->category), $p);
        }
        $samples = array_filter($samples);
        $list = array();
        foreach($samples as $user){
            foreach ($user as $product) {
                if(empty($list[$product]))
                    $list[$product]= 0;
                $list[$product]++;
            }
        }
        arsort($list);
        $list = array_slice($list,0,16,1);
        $list = array_flip($list);
        foreach ($list as $value) {
            $products[] = Product::where('category_id',$value)->orderBy('views','DESC')->first();
        }
        return response()->json($products);
    }
}
