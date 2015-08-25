<?php

namespace mpyw\TwitterText;

class Linkifier {

    private $callbacks = array();

    /**
     * Constructor.
     * Define linkifier callbacks as an argument.
     *
     * @param array<callable> $callbacks
     *     Associative array of callback, which keys should be
     *     "mention", "url", "hashtag", "symbol", "photos", "video".
     *     - "photos" expect 1st argument to be not stdClass, but array<stdClass>.
     *     - Returning nothing means "DO NOT LINKIFY".
     */
    public function __construct(array $callbacks) {
        $this->callbacks = $callbacks + array_fill_keys(
            array('mention', 'url', 'hashtag', 'symbol', 'photos', 'video'),
            function () { }
        );
    }

    /**
     * Linkify texts.
     *
     * @param  string   $text
     * @param  stdClass $entities            See here: https://dev.twitter.com/docs/tweet-entities
     * @param  stdClass [$extended_entities]
     * @return string
     */
    public function linkify($text, \stdClass $entities, \stdClass $extended_entities = null) {
        mb_internal_encoding('UTF-8');
        $info = array();
        foreach (array('user_mention', 'url', 'hashtag', 'symbol') as $type) {
            foreach ($entities->{$type . 's'} as $value) {
                $info[$value->indices[0]] = (object)array(
                    'type'    => $type === 'user_mention' ? 'mention' : $type,
                    'indices' => $value->indices,
                    'value'   => $value,
                );
            }
        }
        if (isset($extended_entities->media[0])) {
            if ($extended_entities->media[0]->type === 'video') {
                $info[$extended_entities->media[0]->indices[0]] = (object)array(
                    'type'    => 'video',
                    'indices' => $extended_entities->media[0]->indices,
                    'value'   => $extended_entities->media[0],
                );
            } else {
                $info[$extended_entities->media[0]->indices[0]] = (object)array(
                    'type'    => 'photos',
                    'indices' => $extended_entities->media[0]->indices,
                    'value'   => $extended_entities->media,
                );
            }
        } elseif (isset($entities->media[0])) {
            $info[$entities->media[0]->indices[0]] = (object)array(
                'type'    => 'photos',
                'indices' => $entities->media[0]->indices,
                'value'   => $entities->media,
            );
        }
        ksort($info);
        $p = 0;
        foreach ($info as $item) {
            $callback = $this->callbacks[$item->type];
            $new = $callback($item->value);
            if (!is_string($new)) {
                continue;
            }
            list($a, $b) = $item->indices;
            $text = mb_substr($text, 0, $a + $p) . $new . mb_substr($text, $b + $p);
            $p += mb_strlen($new) - $b + $a;
        }
        return $text;
    }

    /**
     * Simple wrapper for TwitterText::linkify.
     * Useful when linkifying tweet texts.
     *
     * @param  stdClass $status
     * @return string
     */
    public function linkifyStatus(\stdClass $status) {
        return $this->linkify(
            $status->text,
            $status->entities,
            isset($status->extended_entities) ? $status->extended_entities : null
        );
    }

}
