<?php
​
add_action('wp_ajax_checkWPThemeFunc', 'checkWPThemeFunc');
add_action('wp_ajax_nopriv_checkWPThemeFunc', 'checkWPThemeFunc');
​
function checkWPThemeFunc()
{ 
    $return = array();
    $setThemeUrl = $_POST['getThemeUrl'];
​
    if(isset($_POST['getThemeUrl']))
    {
        $setThemeUrl = $_POST['getThemeUrl'];
    }
​
    //Check WordPress Website
    // error_reporting(0);
    require_once 'mtpscrap.php';
    $searchfor = 'wp-content';
​
    // header('Content-Type: text/plain');
​
    $url      = $setThemeUrl;
    $contents = file_get_dom($url);
    $pattern  = preg_quote($searchfor, '/');
    $pattern  = "/^.*$pattern.*\$/m";
​
    $return['title'] = $contents('title', 0)->getPlainText();
    
    foreach($contents('meta[name]') as $mi)
    {
        $metaInfo[$mi->name] = $mi->content;
​
        if($mi->name == 'description')
        {
            $return['description'] = $mi->content;
        }
    }
​
    // if (!empty($setThemeUrl)) {
    //     $snapShot = getScreenSnap($setThemeUrl);
    //     if ($snapShot)
    //     {
    //         $return['screenShot'] = "<img src='".$snapShot."' class='img-responsive'/>";
    //     }
    //     else
    //     {
    //         $return['screenShot'] = "<img src='https://www.cloudways.com/wp-content/uploads/2020/12/no-image-icon.jpg' alt='No Image Found' class='img-responsive' />";
    //     }     
    // }
​
    if (preg_match_all($pattern, $contents, $matches)) {
        $links = array();
        foreach ($contents('link[href]') as $el) {
            $links[] = $el->href;
​
        }
        $themefound  = false;
        $themefolder = '';
        $plugininfo  = array();
​
        foreach ($links as $link) {
            // $link = 'http://hosting.io/wp-content/themes/kenversionchecking/style.css';
            if (!preg_match('/http/i', $link)) {
                $link = str_replace("//", 'http://', $link);
            }
​
​
            if ((preg_match('/wp-content/i', $link) && preg_match('/plugins/i', $link))) {
                $pluginfolder = explode('/', $link);
                $plugin       = $pluginfolder[5];
                $info         = getplugininfo($plugin);
                if ($info) {
                    $plugininfo[] = getplugininfo($plugin);
                }
​
            }
​
​
​
            if (preg_match('/.css/i', $link) && preg_match('/themes/i', $link) && preg_match('/style/i', $link) && $themefound == false) {
                $themefolder = explode('/', $link);
                $themefolder = $themefolder[5];
                $csscontent  = file_get_dom($link);
                if (preg_match('/theme\s*name/i', $csscontent)) {
                    $themefound = true;
                    preg_match('!/\*[^*]*\*+([^/][^*]*\*+)*/!', $csscontent, $themeinfo);
                    $data = getThemeInfo(nl2br($themeinfo[0]));
                }
            } elseif (preg_match('/.css/i', $link) && $themefound == false) {
                $csscontent = file_get_dom($link);
                if (preg_match('/theme\s*name/i', $csscontent)) {
                    $themefolder = explode('/', $link);
                    $themefolder = $themefolder[5];
​
                    $themefound = true;
                    preg_match('!/\*[^*]*\*+([^/][^*]*\*+)*/!', $csscontent, $themeinfo);
                    $data = getThemeInfo(nl2br($themeinfo[0]));
​
                }
            }
        }
        if ($themefound == true) {
            $return['type']    = 'success';
            $return['payload'] = array('theme' => $data, 'plugins' => $plugininfo);
            echo json_encode($return);
            wp_die();
            return;
        }
​
        if ($themefound == false) {
            $return['type']    = 'error';
            $return['msg']     = 'Sorry theme information not found, it might be using custom theme';
            $return['payload'] = array('theme_folder_name' => $themefolder, 'plugins' => $plugininfo);
            echo json_encode($return);
            wp_die();
            return;
        }
​
    } else {
        $return['type']    = 'error';
        $return['msg']     = 'Sorry this is not a wordpress website';
        $return['payload'] = null;
        echo json_encode($return);
        wp_die();
        return;
    }
​
    echo json_encode($return);
    wp_die();
    return;
}
​
add_action('wp_ajax_screenSnapFunc', 'screenSnapFunc');
add_action('wp_ajax_nopriv_screenSnapFunc', 'screenSnapFunc');
​
function screenSnapFunc()
{
    $return = [];
    $setThemeUrl = $_POST['getThemeUrl'];
​
    if(isset($_POST['getThemeUrl']))
    {
        $setThemeUrl = $_POST['getThemeUrl'];
    }
​
    if (!empty($setThemeUrl)) {
        $snapShot = getScreenSnap($setThemeUrl);
        if ($snapShot)
        {
            $return['screenShot'] = "<img src='".$snapShot."' class='img-responsive'/>";
            echo json_encode($return);
            wp_die();
            return;
        }
        else
        {
            $return['screenShot'] = "<img src='https://www.cloudways.com/wp-content/uploads/2020/12/no-image-icon.jpg' alt='No Image Found' class='img-responsive' />";
            echo json_encode($return);
            wp_die();
            return;
        }     
    }
}
​
//Capture Screenshot
function getScreenSnap($siteURL)
{
    //Url value should not empty and validate url
    if (!empty($siteURL) && filter_var($siteURL, FILTER_VALIDATE_URL)) {
        //call Google PageSpeed Insights API
        $googlePagespeedData = file_get_contents("https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=$siteURL&screenshot=true");
​
        //decode json data
        $googlePagespeedData = json_decode($googlePagespeedData, true);
​
        //screenshot data
        $screenshot = $googlePagespeedData['lighthouseResult']['audits']['final-screenshot']['details']['data'];
        $screenshot = str_replace(array('_','-'),array('/','+'),$screenshot);
        
        //echo '<pre>' . print_r($screenshot, true) . '</pre>';
​
        //display screenshot image
        return $screenshot;
    } else {
        return false;
    }
}
​
function getThemeInfo($info)
{
    $info = trim(strtr($info, array('/*' => '', '*/' => '')));
    $info = explode(PHP_EOL, $info);
    if (count($info) > 0) {
        $return = array();
        foreach ($info as $i) {
            if (preg_match('/theme\s*name/i', $i)) {
                $return['theme_name'] = strip_tags(str_replace(":", '', preg_replace('/theme\s*name/i', '', $i)), '<br>');
            }
            if (preg_match('/theme\s*uri/i', $i)) {
                $return['theme_uri'] = strip_tags(str_replace(":", '', preg_replace('/theme\s*uri/i', '', $i)), '<br>');
            }
            if (preg_match('/author:/i', $i)) {
                $return['author'] = strip_tags(str_replace(":", '', preg_replace('/author/i', '', $i)), '<br>');
            }
            if (preg_match('/author\s*uri/i', $i)) {
​
                $return['author_uri'] = strip_tags(str_replace(":", '', preg_replace('/author\s*URI/i', '', $i)), '<br>');
            }
            if (preg_match('/version/i', $i)) {
                $return['version'] = strip_tags(str_replace(":", '', preg_replace('/version/i', '', $i)), '<br>');
            }
            if (preg_match('/description/i', $i)) {
                $return['description'] = strip_tags(str_replace(":", '', preg_replace('/description/i', '', $i)), '<br>');
            }
            if (preg_match('/license/i', $i)) {
                $return['license'] = strip_tags(str_replace(":", '', preg_replace('/license/i', '', $i)), '<br>');
            }
            if (preg_match('/license\s*uri/i', $i)) {
                $return['license_uri'] = strip_tags(str_replace(":", '', preg_replace('/license\s*uri/i', '', $i)), '<br>');
            }
        }
        return $return;
    } else {
        return false;
​
    }
}
​
function get_content($url) 
{
    $cURLConnection = curl_init();
​
    curl_setopt($cURLConnection, CURLOPT_URL, $url);
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
​
    $response = curl_exec($cURLConnection);
​
    //Check Curl request
    if (curl_errno($ch)) {
        die('Couldn\'t send request: ' . curl_error($ch));
    } else {
        $resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($resultStatus == 200) {
            echo('Succesful Curl');
        } else {
            die('Request failed: HTTP status code: ' . $resultStatus);
        }
    }
​
    curl_close($cURLConnection);
    return $response;
}
​
function getplugininfo($plugin)
{
    $plugininfo = get_content('http://api.wordpress.org/plugins/info/1.0/' . urlencode($plugin));
    $plugininfo = unserialize($plugininfo);
    $plugininfo  = (array)($plugininfo);
    $return = array();
    if (!isset($plugininfo['error'])) {
​
        if (isset($plugininfo['name'])) {
            $return['plugin_name'] = $plugininfo['name'];    
        }
        if (isset($plugininfo['slug'])) {
            $return['slug'] = $plugininfo['slug'];    
        }
        if (isset($plugininfo['author'])) {
            $return['author'] = $plugininfo['author'];    
        }
        if (isset($plugininfo['section']['description'])) {
            $return['description'] = $plugininfo['section']['description'];
        }
        return $return;
    } else {
        //check in the plugin.json file
        $plugininfo = file_get_contents('plugin.json');
        $plugininfo = json_decode($plugininfo, true);
        if ($plugininfo) {
            if (array_key_exists($plugin, $plugininfo)) {
                $return['plugin_name'] = $plugininfo[$plugin]['plugin_name'];
                $return['slug']        = $plugininfo[$plugin]['slug'];
                $return['author']      = $plugininfo[$plugin]['author'];
                $return['description'] = $plugininfo[$plugin]['description'];
                return $return;
            }
        }
        return false;
    }
​
}
