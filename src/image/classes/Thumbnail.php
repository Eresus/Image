<?php
/**
 * Миниатюра изображения
 *
 * @version ${product.version}
 *
 * @copyright 2011, Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Михаил Красильников <m.krasilnikov@yandex.ru>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо (по вашему выбору) с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * Вы должны были получить копию Стандартной Общественной Лицензии
 * GNU с этой программой. Если Вы ее не получили, смотрите документ на
 * <http://www.gnu.org/licenses/>
 *
 * @package Image
 */

/**
 * Миниатюра изображения
 *
 * @package Image
 * @since 1.00
 */
class Image_Thumbnail
{
    /**
     * Имя файла миниатюры
     *
     * @var string
     */
    public $path;

    /**
     * URL миниатюры
     *
     * @var string
     */
    public $url;

    /**
     * Конструктор
     *
     * @param string $path
     *
     * @return Image_Thumbnail
     *
     * @since 1.00
     */
    public function __construct($path)
    {
        $this->$path = $path;
        $this->url = $GLOBALS['Eresus']->root . substr($path, strlen($GLOBALS['Eresus']->froot));
    }
}

