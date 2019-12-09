<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CurlController;
use App\Product;
use App\User;

class IndexController extends Controller
{
    public function getName($href)
    {
        if (preg_match('/san-pham\/(.*?)\//m', $href, $matche))
            return $matche[1];
        if (preg_match('/(.*)?\/(.*?)\/\?/m', $href, $matche))
            return $matche[2];
        if (preg_match('/(.*?)\/\?/m', $href, $matche))
            return $matche[1];
        if (preg_match('/"(.*?).htm/m', $href, $matche))
            return $matche[1];
        if (preg_match('/\/(.*?)\/$/m', $href, $matche))
            return $matche[1];
        return $href;
    }

    public function index()
    {
        $topProduct = Product::orderBy('views', 'desc')->limit(18)->get();
        // $curlController = new CurlController;
        // $list = $curlController->getProduct($topProduct);
        $i = 0;
        foreach ($topProduct as $value) {
            if($this->getName($value->href) !== null){
            $return[$i]['id'] = $value->id;
            $return[$i]['name'] = $this->getName($value->href);
            }
            $i++;
        }
        return response()->json($return);
    }

    public function productInCategory($product_id)
    {
        $category_id = Product::where('id',$product_id)->first()->category_id;
        $topProduct = Product::where('category_id',$category_id)->orderBy('views', 'desc')->limit(18)->get();
        $i = 0;
        foreach ($topProduct as $value) {
            if($this->getName($value->href) !== null){
            $return[$i]['id'] = $value->id;
            $return[$i]['name'] = $this->getName($value->href);
            }
            $i++;
        }
        return response()->json($return);
    }

    public function login(Request $request)
    {
        $return = array();
        $return['status'] = 400;
        if (!empty($request->user_id)) {
            $user = User::find($request->user_id);
            if (!empty($user)) {
                $return['status'] = 200;
                $return['info'] = $user;
                return response()->json($return);
            }
        }
        return response()->json($return);
    }


    public function loginFB(Request $request)
    {
        $return = array();
        $return['status'] = 400;

        if (!empty($request->token)) {
            $info = file_get_contents('https://graph.facebook.com/me?access_token=' . $request->token . '&fields=id,name,email,picture.type(large)');
            $info = json_decode($info);
            if (isset($info->id)) {
                $user = User::where('fb_id', $info->id)->first();
                if (!empty($user)) {
                    $return['status'] = 200;
                    $return['info'] = $user;
                    $user->picture = $info->picture;
                    return response()->json($return);
                } else {
                    $user = new User();
                    $user->id = uniqid(null, true);
                    $user->fb_id = $info->id;
                    $user->name = $info->name;
                    $user->email = $info->email;
                    if ($user->save()) {
                        $return['status'] = 200;
                        $user->picture = $info->picture;
                        $return['info'] = $user;
                        return response()->json($return);
                    }
                }
            }
        }
        return response()->json($return);
    }

    public function history($user_id,$product_id)
    {
        $return = false;
        if($user_id && $product_id)
        {
            if(DB::table('user_product')->insert(
                ['user_id' => $user_id, 'product_id' => $product_id]
            ))
                $return = true;
        }
        return $return;
    }

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
        if (count($listProduct)<2) {
            return null;
        }
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
        if(count($p)<2){
            return null;
        }
        $p = array_slice($p, -4);
        $p = $this->createArray((array)$p);
        array_multisort($p,SORT_DESC);
        $users = null;
        foreach ($p as $val) {
            $users = DB::table('user_category_train');
            foreach ($val as $value) {
                $users->where('category', 'like', "%$value%");
            }
            $users->where('user_id','<>',$user_id);
            $users = $users->limit(200)->get();
            if (count($users) > 100)
            {
                return [$users,$val];
            }
            unset($users);
        }
        return null;
    }

    public function getTrainItem($user_id,$category_id)
    {
        $listProduct = DB::table('user_product')->where('user_id',$user_id)->select('product_id')->get();
        if (count($listProduct)<2) {
            return null;
        }
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
        $p = array_diff($p,[$category_id]);
        if(count($p)<2){
            return null;
        }
        $p = array_slice($p, -4);
        $p = $this->createArray((array)$p);
        array_multisort($p,SORT_DESC);
        $users = null;
        foreach ($p as $val) {
            $users = DB::table('user_category_train');
            foreach ($val as $value) {
                $users->where('category', 'like', "%$value%");
            }
            $users->where('user_id','<>',$user_id);
            $users = $users->limit(200)->get();
            if (count($users) > 100)
            {
                return [$users,$val];
            }
            unset($users);
        }
        return null;
    }


    public function recommend(Request $request)
    {
        set_time_limit(0);
        if(empty($request->user_id))
           return response()->json('null');
    
        $user_id = $request->user_id;
        $getTrain = $this->getTrain($user_id);

        if($getTrain == null) 
            return $this->index();

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
        $list = array_slice($list,0,18,1);

        foreach ($list as $key => $value) {
            $products[] = Product::where('category_id',$key)->orderBy('views','DESC')->first();
        }

        $i=0;
        foreach ($products as $value) {
            if($this->getName($value->href) !== null){
            $return[$i]['id'] = $value->id;
            $return[$i]['name'] = $this->getName($value->href);
            }
            $i++;
        }
        return response()->json($return);
    }
    public function recommendItem(Request $request)
    {
        set_time_limit(0);

        if(empty($request->product_id))
           return response()->json('null');
        if(empty($request->user_id))
            return $this->productInCategory($request->product_id);
        $user_id = $request->user_id;
        $category_id = Product::where('id',$request->product_id)->first()->category_id;
        $getTrain = $this->getTrainItem($user_id,$category_id);

        if($getTrain == null) 
            return $this->productInCategory($request->product_id);

        $users = $getTrain['0'];
        $p = $getTrain['1'];

        $samples = array();

        foreach ($users as $value) {
            $samples[] = array_diff( (array)explode(",", $value->category), $p);
        }
        $samples = array_filter($samples);
        $list = array();
        foreach($samples as $user){
            if (in_array($category_id, $user)){
                foreach ($user as $product) {
                    if(empty($list[$product]))
                        $list[$product] = 0;
                    $list[$product]++;
                }
            }
        }
        unset($list[$category_id]);
        if(!count($list))
            return $this->productInCategory($request->product_id);
        arsort($list);
        $list = array_slice($list,0,18,1);

        foreach ($list as $key => $value) {
            $products[] = Product::where('category_id',$key)->orderBy('views','DESC')->first();
        }

        $i = 0;
        foreach ($products as $value) {
            if($this->getName($value->href) !== null){
            $return[$i]['id'] = $value->id;
            $return[$i]['name'] = $this->getName($value->href);
            }
            $i++;
        }
        $this->history($request->user_id,$request->product_id);
        return response()->json($return);
    }
}
