<?php


//AuthController
const AuthError                             = "Логин или пароль введён неверно";
const NotAuth                               = "Пользователь не авторизован";


//AmoCRMController
const AmoCRMError                           = "Интеграция не подключена";


//Integrations
const IntegrationError                      = "Интеграция уже подключена";
const TaskError                             = "Задача не найдена";


//BitrixController
const BitrixConnectionError                 = "Не скачано приложение в маркете Битрикс24";
const BitrixNotFound                        = "Битрикс24 не найден";


//MegaplanController
const MegaplanConnectionError                = "Не скачано приложение в маркете Мегаплана";
const MegaplanNotFound                       = "Мегаплан не найден";


//DialogController
const DialogNotFound                        = "Диалог не найден";
const DialogExists                          = "Диалог существует";


//UserController
const UserPassUpdateError                   = "Не удалось обновить пароль";
const UserNotFound                          = "Пользователь не найден";
const PassMotMatch                          = "Пароли не совпадают";
const UserIsExist                           = "Пользователь с таким логином уже существует";
const UserEmailIsExist                      = "Пользователь с такой почтой уже существует";
const UserInvalidLogin                      = "Недопустимый логин. Логин может состоять из английских букв и цифр";
const UserInvalidEmail                      = "Недопустимый email";


//FolderController
const FolderIsExist                         = "Папка с таким именем уже существует";
const FolderNotFound                        = "Папка не найдена";
const FolderEditingProhibited               = "Редактирование папки запрещено";


//MassSendingController
const MassSendingIsExist                    = "Рассылка с таким именем уже существует";
const MassSendingNotFound                   = "Рассылка не найдена";


//DynamicMassSendingController
const DynamicMassSendingIsExist             = "Массовая рассылка с таким именем уже существует";
const DynamicMassSendingNotFound            = "Массовая рассылка не найдена";


//AutoresponderController
const AutoresponderIsExist                  = "Автоответчик с таким именем уже существует";
const AutoresponderNotFound                 = "Автоответчик не найден";
const SyntaxErrorConditions                 = "Синтаксическая ошибка условия, возможно не корректная последовательность операторов";


//VenomBotController
const UpdateSessionWhatsappError            = "Не удалось обновить сессию";
const ResourceNotFound                      = "Ресурс с указанным uid не найден";


//UploadController
const FilesIsNull                           = "Файлы не указаны";


//WhatsappController
const WhatsappIsExists                      = "Whatsapp уже существует";
const WhatsappNotFound                      = "Whatsapp не найден";
const WhatsappErrorActivate                 = "Не удалось активировать аккаунт";
const WhatsappQRNotFound                    = "QR код не найден";


//InstagramController
const InstagramIsExists                      = "Instagram уже существует";
const InstagramNotFound                      = "Instagram не найден";
const InstagramInvalidCode                   = "Неверный код";
const UpdateSessionInstagramError            = "Не удалось обновить сессию";


//InstagramApiController
const InstagramApiIsExists                    = "Instagram уже существует";
const InstagramApiNotFound                    = "Instagram не найден";
const InstagramApiNoAccountsToConnect         = "Нет аккаунтов для подключения";
const InstagramApiIsActive                    = "Уже активирован";


//FacebookController
const FacebookIsExists                        = "Facebook уже существует";
const FacebookNotFound                        = "Facebook не найден";
const FacebookAuthError                       = "Не удалось подключить аккаунт";


//MessageContloller
const TypeNotSupported                        = "Тип не поддерживается";
const MessageSendingError                     = "Ошибка отправки сообщения";


//Tariff
const TariffNotFound                          = "Тариф не найден";
const TariffIsExist                           = "Тариф уже существует";
const UserTariffNotFound                      = "Пользовательский тариф не найден";
const TariffDoesNotCompleted                  = "Тариф не завершён";


//Robokassa
const PaymentNotFound                         = "Ошибка оплаты";


//Affiliate
const UrlNameAlreadyExist                     = "Имя для ссылки уже используется";
const UrlNotFound                             = "Ссылка не найдена";
const CardNumberInvalid                       = "Номер карты введён неверно";
const OperationNotFound                       = "Операций не выведено";
const NotAcceptRights                         = "Пользователь не принял правила";


//Purse
const PurseNotFound                           = "Кошелек не найден";
const InsufficientFunds                       = "Недостаточно средств";


//IgnoreList
const PhoneAlreadyIgnore                      = "Телефон уже добавлен в игнор-лист";
const IgnoreListNotFound                      = "Игнор-лист не найден";


const FileFormatInvalid                     = "Неверный формат файла";
const OperationError                        = "Невозможно выполнить";
const ParameterIsNull                       = "Передан пустой параметр:";
const DataTypeError                         = "не соответствует типу данных:";
const IntMax                                = "превышает максимальное значение:";
const IntMin                                = "меньше минимального значения:";
const StrMax                                = "превышает максимальную длину:";
const StrMin                                = "меньше минимальной длины:";
const CSRFTokenInvalid                      = "Недействительный токен CSRF";
const ApiTokenInvalid                       = "Недействительный api токен";
const AccessIsDenied                        = "Доступ запрещен";
const NotActive                             = "Не активирован";
