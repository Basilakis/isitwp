<?php
error_reporting(0);
require_once 'mtpscrap.php';
$searchfor = 'wp-content';

// header('Content-Type: text/plain');

$return = array();

$url      = $_GET['url'];
$contents = file_get_dom($url);
$pattern  = preg_quote($searchfor, '/');
$pattern  = "/^.*$pattern.*\$/m";
if (preg_match_all($pattern, $contents, $matches)) {
    $links = array();
    foreach ($contents('link[href]') as $el) {
        $links[] = $el->href;

    }
    $themefound  = false;
    $themefolder = '';
    $plugininfo  = array();
    foreach ($links as $link) {
        // $link = 'http://hosting.io/wp-content/themes/kenversionchecking/style.css';
        if (!preg_match('/http/i', $link)) {
            $link = str_replace("//", 'http://', $link);
        }

        if ((preg_match('/wp-content/i', $link) && preg_match('/plugins/i', $link))) {
            $pluginfolder = explode('/', $link);
            $plugin       = $pluginfolder[5];
            $info         = getplugininfo($plugin);
            if ($info) {
                $plugininfo[] = getplugininfo($plugin);
            }

        }

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

                $themefound = true;
                preg_match('!/\*[^*]*\*+([^/][^*]*\*+)*/!', $csscontent, $themeinfo);
                $data = getThemeInfo(nl2br($themeinfo[0]));

            }
        }
    }
    if ($themefound == true) {
        $return['type']    = 'success';
        $return['payload'] = array('theme' => $data, 'plugins' => $plugininfo);
        echo json_encode($return);
        return;
    }

    if ($themefound == false) {
        $return['type']    = 'error';
        $return['msg']     = 'Sorry theme information not found, it might be using custom theme';
        $return['payload'] = array('theme_folder_name' => $themefolder, 'plugins' => $plugininfo);
        echo json_encode($return);
        return;
    }

} else {
    $return['type']    = 'error';
    $return['msg']     = 'Sorry this is not a wordpress website';
    $return['payload'] = null;
    echo json_encode($return);
    return;
}

function getThemeInfo($info, $themefolder)
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

    }
}

function getplugininfo($plugin)
{
    $plugininfo = file_get_contents('http://api.wordpress.org/plugins/info/1.0/' . $plugin);
    $plugininfo = unserialize($plugininfo);
    if ($plugininfo) {
        $return['plugin_name'] = $plugininfo['name'];
        $return['slug']        = $plugininfo['wp-management-controller'];
        $return['author']      = $plugininfo['author'];
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
}

echo json_encode($return);
return;
