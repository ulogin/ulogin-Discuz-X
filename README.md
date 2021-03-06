# uLogin

Donate link: http://ulogin.ru  
Tags: ulogin, login, social, authorization  
Requires at least: X2.5  
Tested up to: X3.1  
Stable tag: 2.0  
License: GNU General Public License, version 2  

**uLogin** — это инструмент, который позволяет пользователям получить единый доступ к различным Интернет-сервисам без необходимости повторной регистрации,
а владельцам сайтов — получить дополнительный приток пользователей из социальных сетей и популярных порталов (Google, Яндекс, Mail.ru, ВКонтакте, Facebook и др.)

## Установка
- Распакуйте содержимое папки upload из архива плагина в корень сайта.
- Зайдите в административную панель сайта Discuz!X.
- В разделе "Плагины" найдите uLogin 2.0 и нажмите кнопку "Установка".
- Включите модуль и он заработает сразу с настройками по умолчанию.

Более детальную информацию смотрите на сайте https://ulogin.ru/help.php

*Если у вас на сайте уже был установлен плагин uLogin, то вам необходимо удалить файл /source/class/class_ulogin.php*

## Модуль "uLogin"

Данный модуль находится в Админке в разделе *"Плагины"*.

Здесь задаются: 
 
**uLogin ID форма входа:** общее поле для всех виджетов uLogin, необязательный параметр (см. *"Настройки виджета uLogin"*);
**uLogin ID форма синхронизации:** общее поле для всех виджетов uLogin, необязательный параметр (см. *"Настройки виджета uLogin"*);
**Сохранять ссылку на профиль:** записывать в *Личные данные* пользователя поле *Вебсайт*;
**Отправлять письмо при регистрации нового пользователя:** выслать письмо пользователю после его регистрации с его логином и паролем;

## Настройки виджета uLogin

При установке расширения uLogin авторизация пользователей будет осуществляться с настройками по умолчанию.  
Для более детальной настройки виджетов uLogin Вы можете воспользоваться сервисом uLogin.  

Вы можете создать свой виджет uLogin и редактировать его самостоятельно:

- для создания виджета необходимо зайти в "Личный Кабинет" (ЛК) на сайте http://ulogin.ru/lk.php
- добавить свой сайт к списку "Мои сайты" и на вкладке "Виджеты" добавить новый виджет. После этого вы можете отредактировать свой виджет.

В графе "Возвращаемые поля профиля пользователя" вы можете включить необходимые поля, например, **Пол** или **Дата рождения**, Discuz запишет эти параметры
в соответствующие поля Пользователя при регистрации, или обновит их при авторизации, если они пустые.

**Важно!** Для успешной работы плагина необходимо включить в обязательных полях профиля поле **Еmail** в Личном кабинете uLogin.  
Заполнять поля в графе "Тип авторизации" не нужно, т.к. uLogin настроен на автоматическое заполнение данного параметра.

Созданный в Личном Кабинете виджет имеет параметры **uLogin ID**.  
Скопируйте значение **uLogin ID** вашего виджета в соответствующее поле в настройках плагина на вашем сайте и сохраните настройки.   

Если всё было сделано правильно, виджет изменится согласно вашим настройкам.


## Особенности

Для ручного вывода панели авторизации в любом месте шаблона темы Discuz используйте класс ulogin функцию getPanelCode()

		ulogin::getPanelCode();
	
Описание функции:

		/**
		* @param int $place - указывает, какую форму виджета необходимо выводить
		* (0 - форма входа, 1 - форма синхронизации). Значение по умолчанию = 0
		* @return string(html)
		*/
		public static function getPanelCode($place = 0)`

Для вывода списка аккаунтов пользователя Discuz используйте класс ulogin функцию getuloginUserAccountsPanel()

		ulogin::getuloginUserAccountsPanel();
	
Описание функции:

		/**
		*
		* @param int $user_id - ID пользователя (значение по умолчанию = текущий пользователь)
		* @return string(html)
		*/
		public static function getuloginUserAccountsPanel($user_id = 0)

## Изменения

####2.0.0.
  * Настройки модуля доступны в Админке во вкладке *"Плагины" - "uLogin"*
  * Добавлена новая страница синхронизации/привязки профилей uLogin в настройках профиля *Аккаунты соц.сетей*.
  * Изменение в структуре таблицы *ulogin_member*. Добавлено поле *network*.
  * Реализована ajax синхронизация.
  * Улучшена генерация логина пользователя.
  * Добавлено обновление аватара пользователя из социальной сети при регистрации/авторизации.
 
####1.0.0.
* Релиз.
