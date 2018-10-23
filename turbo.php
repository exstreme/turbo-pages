<?php
/**
 *  * Created by PhpStorm.
 *  * User: exstreme
 *  * Date: 22.10.18 13:27
 *  * Link: https://protectyoursite.ru
 *  * Version: 1.2.1
 */
header('Content-Type: text/html; charset=utf-8');

/* Configuration block
Здесь задаются переменные, которые будут использоваться, необходимо заменить на свои значения!
*/
$siteurl="https://protectyoursite.ru"; // Задаём адрес сайта без слеша на конце
$title = "Удаление вирусов на сайте";
$description = "Чистка вебсайтов от вирусов на Joomla, Wordpress, Modx, Drupal, Magento, Bitrix и других CMS, установка защиты, устранение уязвимостей, гарантия на работу.";
$author = "Protect Your Site";
/* Добавляем данные для шапки
Удалить данную строку, если шапка не нужна */
$logo = "/images/audit-bezopasnosti.jpg"; // Указываем логотип со слешем в начале
$menutype = "mainmenu"; //Тип меню
define( 'TURBO_HEADER', true );
/* Конец настроек шапки */
/* Расскоментируйте, если хотите подгружать комментарии */
define( 'TURBO_COMMENTS', true );
/* Configuration block end */

define( '_JEXEC', 1 );
if ( file_exists( __DIR__ . '/defines.php' ) ) {
    include_once __DIR__ . '/defines.php';
}
if ( !defined( '_JDEFINES' ) ) {
    define( 'JPATH_BASE', __DIR__ );
    require_once JPATH_BASE . '/includes/defines.php';
}
require_once JPATH_BASE . '/includes/framework.php';
$app = JFactory::getApplication('site');

/**
 * Получаем комментарии для каждого материала, но не больше 40
 * @param $id
 */
function get_comments($id){
    $db = JFactory::getDbo();
    $query = $db->getQuery( true )
        ->select($db->quoteName('id'))
        ->select($db->quoteName('name'))
        ->select($db->quoteName('comment'))
        ->select($db->quoteName('date'))
        ->from( '#__jcomments' )
        ->where('published=1') // Только опубликованные
        ->where("object_id=$id"); // Задаём материал
    $list = $db->setQuery( $query,0,39 )->loadObjectList();
    return $list;
}
/* Выборка с базы данных, добавить необходимые параметры или закомментировать лишние */
$db = JFactory::getDbo();
$query = $db->getQuery( true )
    ->select($db->quoteName('id'))
    ->select($db->quoteName('catid'))
    ->select($db->quoteName('title'))
    ->select($db->quoteName('publish_up'))
    ->select($db->quoteName('introtext'))
    ->select($db->quoteName('fulltext'))
    ->from( '#__content' )
    ->where('state=1') // Только опубликованные материалы
    ->where('access=1') // Доступные для всех
    ->where("catid IN ('2','9')"); // Задаём отдельные категории
$list = $db->setQuery( $query )->loadObjectList();
if ( defined( 'TURBO_HEADER' ) ) {
    $query = $db->getQuery( true )
        ->select($db->quoteName('id'))
        ->select($db->quoteName('title'))
        ->select($db->quoteName('link'))
        ->from( '#__menu' )
        ->where('published=1') // Только опубликованные
        ->where('access=1') // Доступные для всех
        ->where("menutype='$menutype'"); // Задаём тип меню
    $menu = $db->setQuery( $query,0,9 )->loadObjectList();
}

$xml='<?xml version="1.0" encoding="utf-8"?>
<rss
    xmlns:yandex="http://news.yandex.ru"
    xmlns:media="http://search.yahoo.com/mrss/"
    xmlns:turbo="http://turbo.yandex.ru"
    version="2.0">
	<channel>
		<title>'.$title.'</title>
		<description><![CDATA['.$description.']]></description>
		<link>'.$siteurl.'/</link>
		<lastBuildDate>'.date(DATE_ATOM).'</lastBuildDate>
		<language>ru-ru</language>';
foreach($list as $item) {
    if ( defined( 'TURBO_COMMENTS' ) ) {
        $comments = get_comments($item->id);
    }
    $link = $siteurl.\Joomla\CMS\Router\Route::_('index.php?option=com_content&view=article&id='.$item->id.'&catid='.$item->catid);
    $introtext = htmlspecialchars_decode(str_ireplace('src="images','src="'.$siteurl.'/images',$item->introtext));
    $introtext = str_ireplace('href="#','href="'.$link.'/#',$introtext);
    $introtext = str_ireplace('src="/','src="'.$siteurl.'/',$introtext);
    $content = $introtext;
    if(!empty($item->fulltext)){
        $fulltext = htmlspecialchars_decode(str_ireplace('src="images','src="'.$siteurl.'/images',$item->fulltext));
        $fulltext = str_ireplace('href="#','href="'.$link.'/#',$fulltext);
        $fulltext = str_ireplace('src="/','src="'.$siteurl.'/',$fulltext);
        $content .= $fulltext;
    }
    $xml.='
			<item turbo="true">
			<title>'.htmlspecialchars($item->title).'</title>
			<link>'.$link.'</link>';
    $xml.='<turbo:content><![CDATA[';
    if ( defined( 'TURBO_HEADER' ) ) {
        $xml.='<header>
                       <figure>
                           <img
                            src="'.$siteurl.$logo.'" />
                       </figure>
                       <h1>'.$title.'</h1>
                       <h2>'.$description.'</h2>
                       <menu>';
        foreach($menu as $menuitem){
            $xml.='<a href="'.$siteurl.\Joomla\CMS\Router\Route::_($menuitem->link).'">
								'.htmlspecialchars($menuitem->title).'
						   </a>';
        }
        $xml.='</menu>
                </header>';
    }
    $xml.=$content;
    $xml.='<div data-block="share" data-network="vkontakte, twitter, facebook, google, telegram, odnoklassniki"></div>'; // Добавляем кнопки поделиться в соцсети
    if(!empty($comments)) {
        $xml.='<div data-block="comments" data-url="'.$link.'#addcomments">';
        foreach ($comments as $comment) {
            $xml .= '<div
                data-block="comment"
                data-author="' . $comment->name . '" 
                data-subtitle="' . $comment->date . '"
               >
                   <div data-block="content">
                       <p>
                            ' . $comment->comment . '
                       </p>
                   </div> 
               </div>';
        }
        $xml.='</div>';
    }
    $xml.=']]></turbo:content>
			<author>'.$author.'</author>
			<pubDate>'.$item->publish_up.'</pubDate>
		</item>';
}
$xml.='</channel>
</rss>';
//echo $xml; 
if (file_put_contents($_SERVER['DOCUMENT_ROOT'].'/turbo.xml', $xml))
{
    echo "XML файл сгенерирован";
}
else
{
    echo "Неизвестная ошибка";
}
?>