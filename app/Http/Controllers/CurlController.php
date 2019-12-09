<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CurlController extends Controller
{

    public function cURL($url, $post = false, $data = array())
    {

        $curl = curl_init();

        if (!$curl) {
            die("Couldn't initialize a cURL handle");
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        if ($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $html = curl_exec($curl);

        // Check if any error has occurred 
        if (curl_errno($curl)) {
            echo 'cURL error: ' . curl_error($curl);
        } else {
            return $html;
        }

        curl_close($curl);
    }
    public function multiple_threads_request($nodes)
    {
        $mh = curl_multi_init();
        $curl_array = array();
        foreach ($nodes as $i => $url) {
            $curl_array[$i] = curl_init($url);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $curl_array[$i]);
        }
        $running = NULL;
        do {
            usleep(10000);
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        $res = array();
        foreach ($nodes as $i => $url) {
            $res[$url] = curl_multi_getcontent($curl_array[$i]);
        }

        foreach ($nodes as $i => $url) {
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);
        return $res;
    }

    public function getProduct($listProduct)
    {
        $url = array();
        foreach($listProduct as $val){
            $q = urlencode($this->getName($val->href));
            if(in_array('https://www.sendo.vn/m/wap_v2/search/product?p=1&platform=web&q=' . $q . '&s=10&search_algo=algo6&sortType=rank',$url))
                $url[] = 'https://www.sendo.vn/m/wap_v2/search/product?p=1&platform=web&q=' . $q . ''.random_int(1,100).'&s=10&search_algo=algo6&sortType=norder_30_desc';
            else
                $url[] = 'https://www.sendo.vn/m/wap_v2/search/product?p=1&platform=web&q=' . $q . '&s=10&search_algo=algo6&sortType=rank';
        }
        $source = $this->multiple_threads_request($url);
        return $this->getInfoProduct($source);
    }

    public function getInfoProduct($source)
    {
        foreach($source as $value){
            $value = json_decode($value);
            if ($value->status->code == 200) {
                $data = $value->result->data;
                if (!empty($data[0])) {
                    $links[] = 'https://www.sendo.vn/m/wap_v2/full/san-pham/'.str_ireplace('.html/', '', $data[0]->cat_path);
                }
                else{
                    $links[] = 'https://www.sendo.vn/m/wap_v2/full/san-pham/ap-dung-tai-hcm-iphone-11-pro-max-64gb-xanh-reu-00606110-22724219';
                }
            }
        }
        $list = $this->multiple_threads_request($links);
        return $list;
    }

    public function getName($href)
    {
        if (preg_match('/san-pham\/(.*?)\//m', $href, $matche))
            return $matche[1];
        if (preg_match('/(.*)?\/(.*?)\/\?/m', $href, $matche))
            return $matche[2];
        if (preg_match('/"(.*?).htm/m', $href, $matche))
            return $matche[1];
        return $href;
    }
}
