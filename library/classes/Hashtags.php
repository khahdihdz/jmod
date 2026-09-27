<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

namespace Library;

/**
 * Класс хештегов
 * Class Hashtags
 * @package Library
 * @author  Koenig(Compolomus)
 */
class Hashtags
{
    /**
     * не обязательный аргумент, индификатор статьи
     * @var bool|int
     */
    private $lib_id = false;

    /**
     * @var PDO $db
     */
    private $db;

    /**
     * Hashtags constructor.
     * @param int $id
     */
    public function __construct($id = 0)
    {
        $this->lib_id = $id;
        $this->db = \App::getContainer()->get(\PDO::class);
    }

    /**
     * Получение всех статей по тегу
     * @param $tag
     * @return array|null
     */
    public function getAllTagStats($tag)
    {
        $stmt = $this->db->prepare('SELECT `lib_text_id` FROM `library_tags` WHERE `tag_name` = ?');
        $stmt->execute([$tag]);
        if ($stmt->rowCount()) {
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        } else {
            return null;
        }

        return $res;
    }

    /**
     * Получение всех тегов статьи
     * @param int $tpl
     * @return object|null
     */
    public function getAllStatTags($tpl = 0)
    {
        $stmt = $this->db->prepare('SELECT `tag_name` FROM `library_tags` WHERE `lib_text_id` = ?');
        $stmt->execute([$this->lib_id]);
        if ($stmt->rowCount()) {
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
            $obj = new Links($res);
            if ($tpl == 1) {
                return $obj->proccess('tplTag')->linkSeparator()->result();
            } else {
                return $obj->linkSeparator(', ')->result();
            }
        } else {
            return null; // у статьи нет тегов
        }
    }

    /**
     * Tự động tạo tag từ tiêu đề, giới thiệu và nội dung bài viết.
     * @param string $title
     * @param string $announce
     * @param string $text
     * @param int $limit
     * @return array
     */
    public function generateAutoTags($title, $announce = '', $text = '', $limit = 8)
    {
        $stopWords = [
            'và','hoặc','của','cho','với','trên','trong','ngoài','từ','đến','một','những','các',
            'này','đó','là','có','được','theo','khi','hướng','dẫn','mới','nhất','như','về','tại',
            'sau','trước','cách','để','bằng','thành','phần','giữa','sử dụng','giúp','nhé','này'
        ];

        $source = mb_strtolower(trim($title . ' ' . $announce . ' ' . $text), 'UTF-8');
        $source = preg_replace('/[“”"‘’]/u', '', $source);
        $source = preg_replace('/[^[:alnum:]À-ỹ+#.\- ]/ui', ' ', $source);
        $words = preg_split('/\\s+/u', trim($source), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) return [];

        $titleWords = preg_split('/\\s+/u', mb_strtolower(trim($title), 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
        $titleText = mb_strtolower(trim($title), 'UTF-8');
        $candidates = [];
        $count = count($words);

        for ($i = 0; $i < $count; $i++) {
            $word = trim($words[$i], " .-");
            if ($word === '' || mb_strlen($word, 'UTF-8') < 2 || in_array($word, $stopWords, true)) continue;
            $inTitle = in_array($word, $titleWords, true) || mb_strpos($titleText, $word) !== false;
            $technical = preg_match('/(?:\\d|[+#.\\-])/', $word) || mb_strlen($word, 'UTF-8') <= 4;
            $score = ($inTitle ? 8 : 2) + ($technical ? 2 : 0);
            $candidates[$word] = ($candidates[$word] ?? 0) + $score;

            for ($n = 2; $n <= 3; $n++) {
                if ($i + $n > $count) break;
                $parts = array_slice($words, $i, $n);
                $valid = true;
                foreach ($parts as $part) {
                    $part = trim($part, " .-");
                    if ($part === '' || mb_strlen($part, 'UTF-8') < 2 || in_array($part, $stopWords, true)) { $valid = false; break; }
                }
                if (!$valid) continue;
                $phrase = trim(implode(' ', array_map(static fn($v) => trim($v, " .-"), $parts)));
                if (mb_strlen($phrase, 'UTF-8') < 4 || mb_strlen($phrase, 'UTF-8') > 45) continue;
                $score = ($inTitle ? 10 : 2) + ($n === 3 ? 5 : 3) + (preg_match('/(?:\\d|[+#.\\-])/', $phrase) ? 3 : 0);
                $candidates[$phrase] = ($candidates[$phrase] ?? 0) + $score;
            }
        }

        arsort($candidates, SORT_NUMERIC);
        $result = [];
        foreach ($candidates as $candidate => $score) {
            $candidate = trim($candidate);
            $duplicate = false;
            foreach ($result as $existing) {
                if ($existing === $candidate || mb_strpos($existing, $candidate, 0, 'UTF-8') !== false || mb_strpos($candidate, $existing, 0, 'UTF-8') !== false) {
                    $duplicate = true;
                    break;
                }
            }
            if (!$duplicate) $result[] = $candidate;
            if (count($result) >= $limit) break;
        }
        return $result;
    }

    /**
     * Добавление тега
     * @param $tags
     * @return int|null
     */
    public function addTags($tags)
    {
        if (empty($tags)) {
            return null;
        } else {
            $stmt = $this->db->prepare('INSERT INTO `library_tags` (`lib_text_id`, `tag_name`) VALUES (?, ?)');
            foreach ($tags as $tag) {
                if (!$this->issetTag($this->validTag($tag))) {
                    $stmt->execute([$this->lib_id, $this->validTag($tag)]);
                }
            }
        }

        return $stmt->rowCount();
    }

    /**
     * Удаление тега
     * @return int
     */
    public function delTags()
    {
        $stmt = $this->db->prepare('DELETE FROM `library_tags` WHERE `lib_text_id` = ?');
        $stmt->execute([$this->lib_id]);
        return $stmt->rowCount();
    }

    /**
     * Проверка существования тега
     * @param string $tag
     * @return bool
     */
    public function issetTag($tag)
    {
        $stmt = $this->db->prepare('SELECT * FROM `library_tags` WHERE `lib_text_id` = ? AND `tag_name` = ?');
        $stmt->execute([$this->lib_id, $tag]);

        return $stmt->rowCount() > 0 ? true : false;
    }

    /**
     * Валидация корректности тега, замена спец символов
     * @param string $tag
     * @return string
     */
    public function validTag($tag)
    {
        return preg_replace(['/[^[:alnum:]]/ui', '/\s\s+/'], ' ', preg_quote(mb_strtolower($tag)));
    }

    /**
     * Массив тегов с релевантностью
     * @return array|bool
     */
    public function arrayCloudTags()
    {
        $result = [];
        $stmt = $this->db->query('SELECT `tag_name`, COUNT(*) as `count` FROM `library_tags` GROUP BY `tag_name` ORDER BY `count` DESC;');
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch()) {
                $result[$row['tag_name']] = $row['count'];
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Рейтинг тегов с сортировкой по алфавиту или релевантности
     * @param string $sort
     * @return array|bool
     */
    public function tagRang($sort = 'cmpalpha')
    {
        $array = $this->arrayCloudTags();
        if ($array) {
            $return = [];
            $max = max(array_values($array));
            $min = min(array_values($array));
            foreach ($array as $key => $value) {
                if ($value > ($max * 0.8)) {
                    $tmp = 2.3;
                } elseif ($value < ($min * 1.2)) {
                    $tmp = 0.8;
                } else {
                    $tmp = round(($max + $value) / $max, 2);
                }

                $return[] = ['name' => $key, 'rang' => $tmp];
            }
            uasort($return, 'Library\Utils::' . $sort);

            return $return;

        } else {
            return false;
        }
    }

    /**
     * Получение ссылок или кэша для отображения
     * @param $array
     * @return string
     */
    public function cloud($array)
    {
        if (sizeof($array) > 0) {
            $obj = new Links($array);

            return $obj->proccess('tplCloud')->linkSeparator(PHP_EOL)->result();
        } else {
            return $this->getCache();
        }
    }

    /**
     * Удаление кэша
     */
    public function delCache()
    {
        file_exists('../files/cache/cmpranglibcloud.dat') ? unlink('../files/cache/cmpranglibcloud.dat') : false;
        file_exists('../files/cache/cmpalphalibcloud.dat') ? unlink('../files/cache/cmpalphalibcloud.dat') : false;
    }

    /**
     * Получение кэша, если кэш отсутствует, создает его
     * @param string $sort
     * @return string
     */
    public function getCache($sort = 'cmpalpha')
    {
        if (file_exists('../files/cache/' . $sort . 'libcloud.dat')) {
            return file_get_contents('../files/cache/' . $sort . 'libcloud.dat');
        } else {
            return $this->setCache($sort);
        }
    }

    /**
     * Установка кэша с сортировкой
     * @param string $sort
     * @return string
     */
    public function setCache($sort = 'cmpalpha')
    {
        $obj = new self();
        $tags = $this->db->query('SELECT `id` FROM `library_tags` LIMIT 1')->rowCount();
        $res = ($tags > 0 ? $obj->cloud($obj->tagRang($sort)) : '<p>' . _t('The list is empty') . '</p>');
        file_put_contents('../files/cache/' . $sort . 'libcloud.dat', $res);

        return $this->getCache($sort);
    }
}
