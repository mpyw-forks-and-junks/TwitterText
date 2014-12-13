<?php

// Configure your credentials
const CONSUMER_KEY        = '';
const CONSUMER_SECRET     = '';
const ACCESS_TOKEN        = '';
const ACCESS_TOKEN_SECRET = '';

// Load library
require_once 'TwitterText.php';

// Implement extended class
class MyTwitterText extends TwitterText {
    
    protected function linkifyUserMention(stdClass $user_mention) {
        return sprintf('<a href="http://twitter.com/%1$s">@%1$s</a>', $user_mention->screen_name);
    }
    
    protected function linkifyUrl(stdClass $url) {
        if (!isset($url->expanded_url, $url->display_url)) {
            $url->expanded_url = $url->url;
            $url->display_url = mb_strimwidth($url->url, 0, 25, '...'); 
        }
        $img = self::getImageUrls($url->expanded_url);
        return $img ?
            sprintf('<div><a href="%s">%s<br><img src="%s" alt=""></a></div>',
                $url->expanded_url,
                $url->display_url,
                $img->small
            ) :
            sprintf('<a href="%s">%s</a>',
                $url->expanded_url,
                $url->display_url
            )
        ;
    }
    
    protected function linkifyHashtag(stdClass $hashtag) {
        return sprintf('<a href="http://twitter.com/search/%s">%s</a>',
            urlencode('#' . $hashtag->text),
            '#' . $hashtag->text
        );
    }
    
    protected function linkifySymbol(stdClass $symbol) {
        return sprintf('<a href="http://twitter.com/search/%s">%s</a>',
            urlencode('$' . $symbol->text),
            '$' . $symbol->text
        );
    }
    
    protected function linkifyMediaList(array $media_list) {
        $str = sprintf('<a href="%s">%s</a><br>', $media_list[0]->expanded_url, $media_list[0]->display_url);
        foreach ($media_list as $media) {
            $str .= sprintf('<a href="%s"><img src="%s" alt=""></a>',
                $media->expanded_url,
                $media->media_url . ':thumb'
            );
        }
        return '<div>' . $str . '</div>';
    }
    
}

// Define simple OAuth request function
function twitter_get($url, array $params = array()) { 
    $oauth = array(
        'oauth_consumer_key'     => CONSUMER_KEY,
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp'        => time(),
        'oauth_version'          => '1.0',
        'oauth_nonce'            => md5(mt_rand()),
        'oauth_token'            => ACCESS_TOKEN,
    );
    $base = $oauth + $params;
    uksort($base, 'strnatcmp');
    $oauth['oauth_signature'] = base64_encode(hash_hmac(
        'sha1',
        implode('&', array_map('rawurlencode', array(
            'GET',
            $url,
            http_build_query($base, '', '&', PHP_QUERY_RFC3986)
        ))),
        implode('&', array_map('rawurlencode', array(
            CONSUMER_SECRET,
            ACCESS_TOKEN_SECRET
        ))),
        true
    ));
    foreach ($oauth as $name => $value) {
        $items[] = sprintf('%s="%s"', urlencode($name), urlencode($value));
    }
    $header = 'Authorization: OAuth ' . implode(', ', $items);
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => $url . '?' . http_build_query($params, '', '&'),
        CURLOPT_HTTPHEADER     => array($header),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => 'gzip',
    ));
    return json_decode(curl_exec($ch));
}


$statuses = twitter_get('https://api.twitter.com/1.1/statuses/home_timeline.json');
date_default_timezone_set('Asia/Tokyo');
header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test</title>
</head>
<body>
<?php if (isset($statuses->errors)): ?>
    <p><?=$statuses->errors[0]->message?></p>
<?php elseif (is_array($statuses) && $statuses): ?>
<?php foreach ($statuses as $i => $status): ?>
<?php if (isset($status->retweeted_status)) { $status = $status->retweeted_status; } ?>
    <article>
<?php if ($i):?>
        <hr>
<?php endif; ?>
        <p style="font-size:120%;">
            <img src="<?=$status->user->profile_image_url?>" alt="<?=$status->user->screen_name?>">
            @<?=$status->user->screen_name?>(<?=$status->user->name?>)
        </p>
        <p>
            <pre><?=
                MyTwitterText::factory($status->text)
                ->linkify($status->entities, isset($status->extended_entities) ? $status->extended_entities : null)
            ?></pre>
        </p>
        <p style="font-size:75%;"><?=date('Y-m-d H:i:s', strtotime($status->created_at))?></p>
    </article>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>