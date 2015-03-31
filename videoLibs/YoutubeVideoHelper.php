<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 3/11/15
 * Time: 17:17
 */

class YoutubeVideoHelper {
    function curlGet($URL) {
        $ch = curl_init();
        $timeout = 3;
        curl_setopt( $ch , CURLOPT_URL , $URL );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , 1 );
        curl_setopt( $ch , CURLOPT_CONNECTTIMEOUT , $timeout );
        /* if you want to force to ipv6, uncomment the following line */
        //curl_setopt( $ch , CURLOPT_IPRESOLVE , 'CURLOPT_IPRESOLVE_V6');
        $tmp = curl_exec( $ch );
        curl_close( $ch );
        return $tmp;
    }
    /*
     * function to use cUrl to get the headers of the file
     */
    function get_location($url) {
        $my_ch = curl_init();
        curl_setopt($my_ch, CURLOPT_URL,$url);
        curl_setopt($my_ch, CURLOPT_HEADER,         true);
        curl_setopt($my_ch, CURLOPT_NOBODY,         true);
        curl_setopt($my_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($my_ch, CURLOPT_TIMEOUT,        10);
        $r = curl_exec($my_ch);
        foreach(explode("\n", $r) as $header) {
            if(strpos($header, 'Location: ') === 0) {
                return trim(substr($header,10));
            }
        }
        return '';
    }
    function get_size($url) {
        $my_ch = curl_init();
        curl_setopt($my_ch, CURLOPT_URL,$url);
        curl_setopt($my_ch, CURLOPT_HEADER,         true);
        curl_setopt($my_ch, CURLOPT_NOBODY,         true);
        curl_setopt($my_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($my_ch, CURLOPT_TIMEOUT,        10);
        $r = curl_exec($my_ch);
        foreach(explode("\n", $r) as $header) {
            if(strpos($header, 'Content-Length:') === 0) {
                return trim(substr($header,16));
            }
        }
        return '';
    }
    function get_description($url) {
        $fullpage = $this->curlGet($url);
        $dom = new DOMDocument();
        @$dom->loadHTML($fullpage);
        $xpath = new DOMXPath($dom);
        $tags = $xpath->query('//div[@class="info-description-body"]');
        $my_description='';
        foreach ($tags as $tag) {
            $my_description .= (trim($tag->nodeValue));
        }

        return utf8_decode($my_description);
    }
    public function downloadYoutubeVideo($video_id) {
        $my_video_info = 'http://www.youtube.com/get_video_info?&video_id='.$video_id; //video details fix *1
        $my_video_info = $this->curlGet($my_video_info);

        $thumbnail_url = $title = $url_encoded_fmt_stream_map = $type = $url = '';

        parse_str($my_video_info);

        if(isset($url_encoded_fmt_stream_map)) {
            /* Now get the url_encoded_fmt_stream_map, and explode on comma */
            $my_formats_array = explode(',',$url_encoded_fmt_stream_map);

        } else {
            throw new APIException("Can not get video content from Youtube");
        }
        if (count($my_formats_array) == 0) {
            throw new APIException("No valid Youtube video format");
        }
        /* create an array of available download formats */
        $avail_formats[] = '';
        $i = 0;
        $ipbits = $ip = $itag = $sig = $quality = '';
        $expire = time();
        foreach($my_formats_array as $format) {
            parse_str($format);
            $avail_formats[$i]['itag'] = $itag;
            $avail_formats[$i]['quality'] = $quality;
            $type = explode(';',$type);
            $avail_formats[$i]['type'] = $type[0];
            $avail_formats[$i]['url'] = urldecode($url) . '&signature=' . $sig;
            parse_str(urldecode($url));
            $avail_formats[$i]['expires'] = date("G:i:s T", $expire);
            $avail_formats[$i]['ipbits'] = $ipbits;
            $avail_formats[$i]['ip'] = $ip;
            $i++;
        }

        $format=$this->getMp4Url($avail_formats);
        if($format==null) {
            throw new APIException("Do not exist Mp4 format on youtube file");
        } else {
            $url=$format['url'];

            $this->downloadVideo($url);
        }
    }
    public function downloadVideo($video_url) {
        // folder to save downloaded files to. must end with slash
        set_time_limit (1 * 60 * 60);

        $destination_folder = 'videos/';

        $newfname = $destination_folder .time().'_'.rand(0,10000).'mp4';

        $file = fopen ($video_url, "rb");
        if ($file) {
            $newf = fopen ($newfname, "wb");

            if ($newf)
                while(!feof($file)) {
                    fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
                }
        }

        if ($file) {
            fclose($file);
        }

        if ($newf) {
            fclose($newf);
        }

    }
    private function getMp4Url($avaiable_formats) {
        $first=null;
        foreach ($avaiable_formats as $format) {
            if($format['type']=='video/mp4' ) {
                $first = $format;
                if ($format['quality'] == 'medium') {
                    return $format;
                }
            }
        }
        return $first;
    }

}