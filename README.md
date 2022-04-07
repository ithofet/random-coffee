# Мотивация #

Random Coffee — это сервис для еженедельных встреч между участниками вашего сообщества. Если коротко, то раз в неделю
тебя знакомят со случайным человеком.

Этот репозиторий реализует данный сервис.

# Установка #

## Программы ##

Для работы потребуются следующие программы, заранее установленные и настроенные на вашем сервере:

+ NGINX/APACHE
+ MySQL
+ PHP 7.2 +
+ Composer

## Настройка ВК ##

+ Предварительно Вам нужно разрешить сообщения в сообществе и разрешить работу ботов.
+ Создайте API-ключ с разрешением **доступа к сообщениям сообщества**.
+ В настройка Callback API сервера необходимо:
    + Версия API **5.101**
    + Адрес должен указать расположение файла **input.php** на сервере
    + Создать секретный ключ (отсутствие ключа может привести к нестабильной работе программы)
+ В настройках типа событий нужно выбрать "Входящее сообщение ", "Исходящее сообщение ", "Действие с сообщением ", "
  Разрешение на получение "

## Настройка базы данных ##

В MySQL нужно выполнить файлы из папки SQL. Сначала **init.sql**, затем в произвольном порядке.

После нужно выполнить запрос:
<code>
INSERT INTO adminList (chat_id, degree, appointedBy) VALUES (ID_ВАШЕЙ_СТРАНИЦЫ_ВК, 3, 0);
</code>

## Настройка PHP и конфигов ##

В папке /class нужно выполнить <code> composer install </code>

В файл /class/auth/keys.php нужно внести данные Вашего сообщества

В файле /class/Database/DataBaseAuth.php нужно внести хост/логин/пароль MySQL

После выполнения всех действий и подтверждения ссылки в настройках API сообщества настройка завершена

# Эксплуатация # 

## Система рангов ##

Бот имеет 3 ранга:

+ Владелец - может просматривать список Администраторов/Редакторов, назначать Администраторов, передавать управление
  ботом
+ Администратор - может назначать редакторов и начинать раунд
+ Редактор - может одобрять/отклонять заявки на участие, смотреть статистику

**ВАЖНО:** Звание Владелец имеет только один человек

Список команд доступен по команде <code> /ls </code>

## Работа с <code>/approve</code> ##

При использовании команды <code>/approve</code> бот выводит количество новых заявок и предлагает 4 варианта:

+ <code>/yes</code> - подтверждает анкету участника
+ <code>/always yes</code> - подтверждает анкету участника и будет автоматически подтверждать заявки этого участника в
  будущем
+ <code>/no</code> - отклоняет анкету участника
+ <code>/ban</code> - игнорирует все заявки на участие от этого пользователя

В случае отсутствия заявок будет выведено
> Новых заявок нет!

## Начала раунда ##

Если вы решите начать раунд RandomCoffee, то воспользуйтесь командой `/start_event` . Доступ к команде
имеют администраторы и владельцы. В случае нечётного количества участников `/start_event` выведет сообщение с ссылкой на
страницу человека, который остался без пары (сообщение получит только тот, кто ввел команду)

# Обновления

## V 1.1

+ Теперь приложение работает полностью на PHP. Использование C++ и установка компилятора больше не требуется.

+ Добавлена команда <code>/send_notify</code>. После активации команды, всем пользователям будет отправлено сообщение с приглашением
на новый раунд. Текст можно задать в файле `/Database/AdminList.php` (340). Если пользователь не захочет получать сообщения
в будущем, он может воспользоваться командой `"Отписаться"`
+ Для выполнения `/send_notify` требуется звание _Администратор и выше_

P.S Заблокированным пользователям не приходят уведомления о новом раунде
