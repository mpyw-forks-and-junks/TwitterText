<?php

// Configure your credentials
$ck = '';
$cs = '';
$ot = '';
$os = '';
$tz = 'Asia/Tokyo';

// Use namespace
use mpyw\TwitterText\Linkifier;
use mpyw\TwitterText\ImageUtil;

// Load library (Or use composer style autoloader)
require 'build/TwitterText.phar'; // require 'vendor/autoload.php';

// Implement extended class
class MyTwitterText extends Linkifier {
    
    protected function linkifyUserMention(\stdClass $user_mention) {
        return sprintf('<a href="http://twitter.com/%1$s">@%1$s</a>', $user_mention->screen_name);
    }
    
    protected function linkifyUrl(\stdClass $url) {
        if (!isset($url->expanded_url, $url->display_url)) {
            $url->expanded_url = $url->url;
            $url->display_url = mb_strimwidth($url->url, 0, 25, '...'); 
        }
        $img = ImageUtil::getUrls($url->expanded_url);
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
    
    protected function linkifyHashtag(\stdClass $hashtag) {
        return sprintf('<a href="http://twitter.com/search/%s">%s</a>',
            urlencode('#' . $hashtag->text),
            '#' . $hashtag->text
        );
    }
    
    protected function linkifySymbol(\stdClass $symbol) {
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

// Wrapper for htmlspecialchars()
function h($str, $double = true) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8', $double);
}

// Quickly install TwistOAuth
function install_twistoauth() {
    $url = 'https://raw.githubusercontent.com/mpyw/TwistOAuth/master/build/TwistOAuth.phar';
    switch (true) {
        case !$tmp = @fopen(__DIR__ . '/TwistOAuth.phar', 'wb'):
        case !$fp = @fopen($url, 'rb'):
        case !@stream_copy_to_stream($fp, $tmp):
            $error = error_get_last();
            throw new \Exception($error['message']);
    }
}

date_default_timezone_set($tz);
ob_start();
register_shutdown_function(function () {
    file_put_contents('response.html', ob_get_flush());
});
$code = 200;

try {
    if (!is_file(__DIR__ . '/TwistOAuth.phar')) {
        install_twistoauth();
    }
    require __DIR__ . '/TwistOAuth.phar';
    $to = new \TwistOAuth($ck, $cs, $ot, $os);
    $statuses = $to->get('statuses/home_timeline');
} catch (\Exception $e) {
    $code = $e->getCode() ?: 500;
    $error = $e->getMessage();
}

header('Content-Type: text/html; charset=UTF-8', true, $code);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test</title>
</head>
<body>
<?php if (isset($error)): ?>
    <p><?=h($error)?></p>
<?php elseif (is_array($statuses) && $statuses): ?>
<?php foreach ($statuses as $i => $status): ?>
<?php if (isset($status->retweeted_status)) { $status = $status->retweeted_status; } ?>
<?php if ($i):?>
    <hr>
<?php endif; ?>
    <article>
        <p style="font-size:120%;">
            <img src="<?=h($status->user->profile_image_url)?>" alt="<?=h($status->user->screen_name)?>">
            @<?=h($status->user->screen_name)?>(<?=h($status->user->name)?>)
        </p>
        <p>
            <pre><?=
                MyTwitterText::factory($status->text)
                ->linkify($status->entities, isset($status->extended_entities) ? $status->extended_entities : null)
            ?></pre>
        </p>
        <p style="font-size:75%;"><?=h(date('Y-m-d H:i:s', strtotime($status->created_at)))?></p>
    </article>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
