<?php
/**
 * Image
 *
 * @version 1.00
 *
 * @copyright 2011, Eresus Project, http://eresus.ru/
 * @license http://www.gnu.org/licenses/gpl.txt GPL License 3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Image
 * @author Михаил Красильников <mihalych@vsepofigu.ru>
 *
 * $Id: Thumbnail.php 10415 2011-07-14 14:30:46Z mk $
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
	//-----------------------------------------------------------------------------
}