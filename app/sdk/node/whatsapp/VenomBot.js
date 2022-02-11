/**
 * Libs
 */
const http = require('http');
const venom = require('venom-bot');
const request = require('request');
const dotenv = require('dotenv');
const net = require('net');
const Exception = require('./Exception');


/**
 * Config
 */
dotenv.config()

const Domain = (process.env.HTTP_SSL ? "https://" : "http://") + process.env.DOMAIN_API;
const ApiToken = process.env.VENOM_API_TOKEN;
const UploadToken = process.env.UPLOAD_FILE_TOKEN;
const GetToken = process.env.GET_FILE_TOKEN;
const port = process.env.VENOM_PORT;
const ip = process.env.VENOM_IP;

const LogTypeError = "Error";
const LogTypeFatalError = "Fatal Error";
const LogTypeWarning = "Warning";
const LogTypeInfo = "Info";


/**
 * Technical variables
 */
var Venoms = {};


/**
 * Init
 */
var __consoleLog = console.log;
var __consoleError = console.error;
console.log = (Message) => {
    let date = new Date();
    Message = `[${FormatNumber(date.getDate())}:${FormatNumber(date.getMonth())}:${FormatNumber(date.getFullYear())} ${FormatNumber(date.getHours())}:${FormatNumber(date.getMinutes())}:${FormatNumber(date.getSeconds())}] {VENOM} >>>> ${Message}`;
    __consoleLog(Message);
    Log(LogTypeInfo, Message);
};

console.error = (Message, ExceptionErr = null) => {
    let date = new Date();
    Message = `[${FormatNumber(date.getDate())}:${FormatNumber(date.getMonth())}:${FormatNumber(date.getFullYear())} ${FormatNumber(date.getHours())}:${FormatNumber(date.getMinutes())}:${FormatNumber(date.getSeconds())}] {VENOM} >>>> ${Message}`;
    __consoleError(Message + (ExceptionErr ? ` Error: ${ExceptionErr.toString()}` : ''));
    Log(LogTypeError, Message, ExceptionErr);
};

process.on('uncaughtException', async function(error) {
    await Log(LogTypeFatalError, "Неизвестная ошибка", error);
});





/**
 * Main
 */
async function Main() {
    http.createServer(function(request, response) {
        if (request.method == 'POST') {
            var body = null;

            request.on('data', async function(data) {
                if (body == null)
                    body = data;
                else
                    body = await Buffer.concat([body, data]);
            });

            request.on('end', async function() {
                await OnHttp(body.toString('utf8'), request, response);
            });
        }
    }).listen(port, ip, () => {});

    console.log(`Server is running at ${ip}:${port}`);
    console.log("Initialization complete");

    AsyncVenomsInit();
}
Main();







/**
 * Methods
 */

async function AsyncVenomsInit() {
    var Whatsapps = await GetWhatsapps();
    for (var i = 0; i < Whatsapps.length; i++) {

        while (true) {
            let InitCount = 0;
            for (const [key, value] of Object.entries(Venoms))
                if (!value.IsInit)
                    InitCount++;

            if (InitCount < 2)
                break;

            await SleepMS(500);
        }

        try {
            VenomInit(Whatsapps[i]);
        } catch (error) {}
    }
}

async function VenomInit(Whatsapp) {
    console.log(`WhatsappId: ${Whatsapp.WhatsappId}; Initialization start...`);
    if (!Whatsapp) {
        console.log(`WhatsappId: ${Whatsapp.WhatsappId}; Initialization error. Whatsapp is empty!`);
        throw new Exception(`Whatsapp is empty`, 2001);
    }

    if (Venoms[Whatsapp.WhatsappId]) {
        if (!Venoms[Whatsapp.WhatsappId].IsInit) {
            console.log(`WhatsappId: ${Whatsapp.WhatsappId}; WhatsApp already in the process of initialization!`);
            console.log(`WhatsappId: ${Whatsapp.WhatsappId}; Waiting for initialization to complete...`);

            while (true) {
                if (!Venoms[Whatsapp.WhatsappId])
                    throw new Exception(`Venom initialization error`, 2002);

                if (Venoms[Whatsapp.WhatsappId].IsInit)
                    return;

                await SleepMS(2000);
            }
        } else
            console.log(`WhatsappId: ${Whatsapp.WhatsappId}; Initialization error. WhatsApp already initialized!`);
        return;
    }

    Whatsapp.IsInit = false;
    Whatsapp.ContactStatuses = [];
    Venoms[Whatsapp.WhatsappId] = Whatsapp;
    let UpdateSessionWhatsappStatus = false;
    let ScanQRCodeAttempt = 0;
    EventWhatsappSessionInvalid(Whatsapp.WhatsappId);

    try {
        console.log(`WhatsappId: ${Whatsapp.WhatsappId}; Initialization...`);

        if (Whatsapp.Proxy)
            console.log(`WhatsappId: ${Whatsapp.WhatsappId}; Use proxy: ${Whatsapp.Proxy.HostName}:${Whatsapp.Proxy.Protocol}`);

        Venoms[Whatsapp.WhatsappId].venom = await venom.create(
            Whatsapp.WhatsappId,
            async(base64Qrimg, asciiQR, attempts, urlCode) => {
                console.log(`WhatsappId: ${Whatsapp.WhatsappId}; Scan QR code. Attempt: ${++ScanQRCodeAttempt}`);

                Venoms[Whatsapp.WhatsappId].QRCode = base64Qrimg;
                setTimeout(() => {
                    if (Venoms[Whatsapp.WhatsappId])
                        delete Venoms[Whatsapp.WhatsappId].QRCode
                }, 30000);
            },
            (statusSession, session) => {
                if (statusSession == "notLogged")
                    UpdateSessionWhatsappStatus = true;

                console.log(`WhatsappId: ${Whatsapp.WhatsappId}; Status: ${statusSession}`);
            }, {
                disableWelcome: true,
                autoClose: 180000,
                logQR: false,
                browserArgs: [
                    (Whatsapp.Proxy ? `--proxy-server=${Whatsapp.Proxy.Protocol}://${Whatsapp.Proxy.HostName}` : ''),
                    '--no-sandbox'
                ],
                //headless: false, // Headless chrome
                createPathFileToken: false,
                multidevice: false
            },
            Whatsapp.VenomSession
        );
        Venoms[Whatsapp.WhatsappId].IsInit = true;

        await SleepMS(1000);

        EventWhatsappConnected(Whatsapp.WhatsappId);

        Venoms[Whatsapp.WhatsappId].Phone = await GetPhone(Whatsapp.WhatsappId);

        if (!await AffordablePhoneCheck(Whatsapp.WhatsappId, Venoms[Whatsapp.WhatsappId].Phone))
            throw new Exception(`Whatsapp with such a phone already exists`, 2008);

        await Venoms[Whatsapp.WhatsappId].venom.onAck(OnAck);
        await Venoms[Whatsapp.WhatsappId].venom.onMessage(onMessage);
        await Venoms[Whatsapp.WhatsappId].venom.onStateChange((state) => { onStateChange(state, Whatsapp.WhatsappId) });

        if (UpdateSessionWhatsappStatus)
            UpdateSessionWhatsapp(Whatsapp.WhatsappId, await Venoms[Whatsapp.WhatsappId].venom.getSessionTokenBrowser());

        setInterval(async () => {
            UpdateSessionWhatsapp(Whatsapp.WhatsappId, await Venoms[Whatsapp.WhatsappId].venom.getSessionTokenBrowser());
        }, 43200000);

        console.log(`WhatsappId: ${Whatsapp.WhatsappId}; Initialization successful!`);
        return;
    } catch (error) {
        await VenomClose(Whatsapp.WhatsappId);
        console.error(`WhatsappId: ${Whatsapp.WhatsappId}; Initialization error.`, error);
        throw new Exception(`Venom initialization error`, 2002);
    }
}


async function Log(Type, Message, error) {
    await new Promise((resolve, reject) => {
        var client = new net.Socket();

        client.on('error', function(ex) {
            resolve();
        });

        client.connect(1436, "localhost", async function() {
            MessObj = {
                Source: 'Venom',
                Type: Type,
                Message: Message,
                UserId: -2
            };

            if (error) {
                if (error instanceof Exception)
                    MessObj.Data = ("\nMessage: " + error.Message + "\nCode: " + error.Code + "\nStack: " + error.Stack);
                else
                    MessObj.Data = ("\nMessage: " + String(error) + "\nStack: " + (new Error().stack));
            }

            await client.write(JSON.stringify(MessObj));
            resolve();
        });
    });
}


async function GetWhatsapps() {
    var __error;
    try {
        var Response = JSON.parse(await RequestGet(`${Domain}/get/venombot/whatsapps`, true));

        if (Response.status == "ok")
            return Response.Whatsapps;
    } catch (error) { __error = error; }

    console.log(`Get Whatsapp accounts error. Error: ${Response ? JSON.stringify(Response) : ''} ${__error ? __error.toString() : ''}`);
    throw new Exception(`Get Whatsapp accounts error`, 2003);
}


async function RequestGet(Url, UseToken = true) {
    return await new Promise(function(resolve, reject) {
        if (UseToken) {
            if (Url.indexOf('?') > -1)
                Url += `&`;
            else
                Url += `?`;

            Url += `token=${ApiToken}`;
        }
        request.get({
            url: Url,
        }, function(err, httpResponse, body) {
            resolve(body);
        });
    });
}


async function RequestPost(Url, Data, UseToken = true) {
    return await new Promise(function(resolve, reject) {
        if (UseToken)
            Data = Object.assign(Data, { "token": ApiToken });
        request.post({
            url: Url,
            headers: { 'content-type': 'application/json' },
            body: JSON.stringify(Data)
        }, function(err, httpResponse, body) {
            resolve(body);
        });
    });
}


async function AffordablePhoneCheck(WhatsappId, Phone) {
    let Result = JSON.parse(await RequestPost(`${Domain}/get/venombot/affordable-phone-check`, { "WhatsappId": WhatsappId, "Phone": Phone }));
    return Result.status == 'ok';
}


function FormatNumber(Value) {
    return Value < 10 ? `0${Value}` : Value;
}


async function SleepMS(time = 15000) {
    await new Promise((resolve, reject) => {
        setTimeout(() => { resolve() }, time);
    });
}


async function EventWhatsappDisconnect(WhatsappId) {
    RequestPost(`${Domain}/event/venombot/whatsapp-disconnect`, { "WhatsappId": WhatsappId });
}


async function EventMessageStatusUpdate(WhatsappId, UserId, Phone, MessageUid, Ack) {
    RequestPost(`${Domain}/event/venombot/message-status-update`, { "WhatsappId": WhatsappId, "Phone": Phone, "MessageUid": MessageUid, "ack": Ack, "user_id": UserId });
}


async function EventWhatsappConnected(WhatsappId) {
    RequestPost(`${Domain}/event/venombot/whatsapp-connected`, { "WhatsappId": WhatsappId });
}

async function UpdateSessionWhatsapp(WhatsappId, Session) {
    RequestPost(`${Domain}/update/venombot/whatsapps/session`, { "WhatsappId": WhatsappId, "VenomSession": Session });
}

async function EventWhatsappSessionInvalid(WhatsappId) {
    RequestPost(`${Domain}/event/venombot/whatsapp-session-invalid`, { "WhatsappId": WhatsappId });
}


async function GetPhone(WhatsappId) {
    return (await Venoms[WhatsappId].venom.getHostDevice()).id.user;
}


async function onStateChange(state, WhatsappId) {
    switch (state) {
        case "CONFLICT":
            await SleepMS(10000);
            console.log(`WhatsappId: ${WhatsappId}; Device conflict`);
            Venoms[WhatsappId].venom.useHere();
            break;

        case "UNPAIRED":
            console.log(`WhatsappId: ${WhatsappId}; Invalid session`);
            await EventWhatsappSessionInvalid(WhatsappId);
            await VenomClose(WhatsappId);
            break;

        case "TIMEOUT":
            console.log(`WhatsappId: ${WhatsappId}; Device offline`);
            await EventWhatsappDisconnect(WhatsappId);
            break;

        case "CONNECTED":
            console.log(`WhatsappId: ${WhatsappId}; Device online`);
            await EventWhatsappConnected(WhatsappId);
            break;
    }
}


async function onMessage(message) {
    var phone, FromPhone;

    if (message.fromMe) {
        phone = message.from.replace(/\D+/g, "");
        FromPhone = message.to.replace(/\D+/g, "");
    } else {
        phone = message.to.replace(/\D+/g, "");
        FromPhone = message.from.replace(/\D+/g, "");
    }
    try {
        Venom = await FindVenomByPhone(phone);
        var Resources = { files: ["null"] };
        //Загрузка файла на сервер
        if (message.mediaData && Object.keys(message.mediaData).length > 0)
            Resources = await UploadFile(Venom.UserId, await Venom.venom.decryptFile(message), message.mimetype, message.filename);

        RequestPost(`${Domain}/put/venombot/whatsapps/message`, { "WhatsappId": Venom.WhatsappId, "UserId": Venom.UserId, "Phone": FromPhone, "Message": message, "ResourcesId": Resources.files[0] });

    } catch (error) {
        console.error(`Error processing new message. Phone: ${phone}`, error);
    }
}


function FindVenomByPhone(Phone) {
    for (const [key, value] of Object.entries(Venoms)) {
        if (value.Phone == Phone) {
            return value;
        }
    }

    throw new Exception("Whatsapp not found", 2006);
}


var AckTemp = [];
async function OnAck(Ack) {
    var phone;
    if (Ack.id.fromMe) {
        phone = Ack.to.replace(/\D+/g, "");
        FromPhone = Ack.from.replace(/\D+/g, "");
    } else {
        phone = Ack.from.replace(/\D+/g, "");
        FromPhone = Ack.to.replace(/\D+/g, "");
    }
    Venom = await FindVenomByPhone(FromPhone);
    if (Ack.ack > 0 && Ack.ack < 3)
        await AckOnMessage(Ack, Venom.WhatsappId)

    setTimeout(() => {
        EventMessageStatusUpdate(Venom.WhatsappId, Venom.UserId, phone, Ack.id._serialized, Ack.ack);
    }, 1000);
}


async function AckOnMessage(Ack, WhatsappId) {
    for (var i = 0; i < AckTemp.length; i++)
        if (AckTemp[i].from == Ack.from && AckTemp[i].to == Ack.to && AckTemp[i].t == Ack.t && AckTemp[i].body == Ack.body, AckTemp[i].id == Ack.id._serialized)
            return;

    AckTemp.push({ "from": Ack.from, "to": Ack.to, "body": Ack.body, "t": Ack.t, "id": Ack.id._serialized });


    var Chat = (await Venoms[WhatsappId].venom.getAllMessagesInChat(Ack.to)).reverse();
    for (var i = 0; i < Chat.length; i++) {
        if (Ack.id._serialized == Chat[i].id) {
            await onMessage(Chat[i]);
            return;
        }
    }
}


async function VenomClose(WhatsappId) {
    if (Venoms[WhatsappId]) {
        if (Venoms[WhatsappId].IsInit === true) {
            try {
                await Venoms[WhatsappId].venom.close();
            } catch (error) {}
        }
        delete Venoms[WhatsappId];
    }
    await EventWhatsappSessionInvalid(WhatsappId);
}


async function EditAvatar(WhatsappId, AvatarUrl) {
    if (!Venoms[WhatsappId])
        throw new Exception("Whatsapp not found", 404);

    var Response = await Venoms[WhatsappId].venom.setProfilePic(`${AvatarUrl}?token=${ApiToken}`);

    if (Response.status != 200)
        throw new Exception("Error edit avatar", Response.status);
}


async function GetChat(WhatsappId, Phone) {
    if (!Venoms[WhatsappId])
        throw new Exception("Whatsapp not found", 404);

    return await Venoms[WhatsappId].venom.loadAndGetAllMessagesInChat(`${Phone}@c.us`, true);
}


async function GetAllChats(WhatsappId) {
    if (!Venoms[WhatsappId])
        throw new Exception("Whatsapp not found", 404);

    var AllChats = await Venoms[WhatsappId].venom.getAllChats();
    var Out = [];
    for (var i = 0; i < AllChats.length; i++) {
        var Temp = AllChats[i].id._serialized.split("@");
        if (Temp[1] == "c.us")
            Out.push(Temp[0]);
    }

    return Out;
}


async function SendMessage(WhatsappId, Phone, Type, Data) {
    if (!Venoms[WhatsappId])
        throw new Exception("Whatsapp not found", 404);

    let Out;
    console.log(`WhatsappId: ${WhatsappId}; Sending message to phone: ${Phone}`);
    switch (Type) {
        case "text":
            Out = await Venoms[WhatsappId].venom.sendText(`${Phone}@c.us`, Data);
            break;

        case "img":
        case "video":
        case "document":
            Out = await Venoms[WhatsappId].venom.sendFileFromBase64(`${Phone}@c.us`, Data.File, Data.FileName, Data.Caption);
            break;

        default:
            throw new Exception(`Unknown message type`, 2007);
            break;
    }
    console.log(`WhatsappId: ${WhatsappId}; Sent!`);
    return Out;
}


async function GetContactStatus(WhatsappId, Phone) {
    if (!Venoms[WhatsappId])
        throw new Exception("Whatsapp not found", 404);

    if (!Venoms[WhatsappId].ContactStatuses[Phone] || (Venoms[WhatsappId].ContactStatuses[Phone] && (Venoms[WhatsappId].ContactStatuses[Phone].Time + 300000) < Date.now())) {
        var Chat = (await Venoms[WhatsappId].venom.getChat(`${Phone}@c.us`));
        Venoms[WhatsappId].ContactStatuses[Phone] = {
            Data: {
                phone: Chat.id.user,
                is_online: Chat.isOnline != null
            },
            Time: Date.now()
        };
    }

    return Venoms[WhatsappId].ContactStatuses[Phone].Data;
}


async function VenomRestart(Whatsapp) {
    try {
        if (Venoms[Whatsapp.WhatsappId])
            await VenomClose(Whatsapp.WhatsappId);
    } catch (error) {}
    await VenomInit(Whatsapp);
}


async function UploadFile(UserId, file, mimetype, filename = "temp") {
    var Resources = await new Promise(async(resolve, reject) => {
        request.post({
            url: `${Domain}/upload/file`,
            headers: { 'content-type': 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW', "Authorization": UploadToken, "User-Id": UserId },
            formData: {
                file: {
                    value: file,
                    options: {
                        filename: filename,
                        contentType: mimetype
                    }
                }
            }
        }, function(err, httpResponse, body) {
            if (!body)
                throw new Exception(`Не удалось загрузить файл на сервер. Ответ: ${body}. Статус: ${httpResponse.statusCode}`, 500);

            try {
                resolve(JSON.parse(body));
            } catch (error) {
                throw new Exception(`Некорректный ответ с сервера. Ответ: ${body}. Статус: ${httpResponse.statusCode}`, 500);
            };
        });
    });

    return Resources;
}


/**
 * Http
 */
async function OnHttp(data, request, response) {
    var out = {};

    try {
        data = JSON.parse(data);

        switch (data.Command) {
            case "SendMessage":
                out.Phone = data.Phone;
                out.WhatsappId = data.WhatsappId;
                try {
                    out.SendMessage = await SendMessage(data.WhatsappId, data.Phone, data.Type, data.Data);
                } catch (error) {
                    out.Error = error.message;
                    out.Code = error.code;
                }
                break;

            case "GetAllChats":
                try {
                    out.GetAllChats = await GetAllChats(data.WhatsappId);
                } catch (error) {
                    out.Error = error.message;
                    out.Code = error.code;
                }
                break;

            case "GetChat":
                try {
                    out.GetChat = await GetChat(data.WhatsappId, data.Phone);
                } catch (error) {
                    out.Error = error.message;
                    out.Code = error.code;
                }
                break;

            case "EditAvatar":
                try {
                    await EditAvatar(data.WhatsappId, data.Avatar);
                    out.EditAvatar = "ok";
                } catch (error) {
                    out.Error = error.message;
                    out.Code = error.code;
                }
                break;

            case "VenomInit":
                try {
                    await VenomInit(data.Whatsapp);

                    out.Phone = Venoms[data.Whatsapp.WhatsappId].Phone;
                    out.VenomInit = "ok";
                } catch (error) {
                    out.Error = error.message;
                    out.Code = error.code;
                }
                break;

            case "VenomClose":
                try {
                    await VenomClose(data.WhatsappId);
                    out.VenomClose = "ok";
                } catch (error) {
                    out.Error = error.message;
                    out.Code = error.code;
                }
                break;


            case "GetContactStatus":
                try {
                    out.GetContactStatus = await GetContactStatus(data.WhatsappId, data.Phone);
                } catch (error) {
                    out.Error = error.message;
                    out.Code = error.code;
                }
                break;


            case "GetQRCode":
                try {
                    if(Venoms[data.WhatsappId])
                        out.GetQRCode = Venoms[data.WhatsappId].QRCode;
                    else
                        out.GetQRCode = null;
                } catch (error) {
                    out.Error = error.message;
                    out.Code = error.code;
                }
                break;

            case "VenomRestart":
                try {
                    await VenomRestart(data.Whatsapp);
                    out.VenomRestart = true;
                } catch (error) {
                    out.Error = error.message;
                    out.Code = error.code;
                }
                break;

            default:
                out.Error = "Unknown command";
                out.Code = 404;
                break;
        }
    } catch (error) {
        out.Error = "Internal error";
        out.Code = 500;
    }

    if (out.Error) {
        out.Status = "error";

        console.error(`Error processing request`, JSON.stringify(out));
    } else
        out.Status = "ok";

    response.end(JSON.stringify(out));
}