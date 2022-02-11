const http = require('http');
const request = require('request');
const { IgApiClient, Feed } = require('instagram-private-api')
const {
    withFbnsAndRealtime,
    GraphQLSubscriptions,
    SkywalkerSubscriptions
} = require('instagram_mqtt');
const dotenv = require('dotenv');
const Exception = require('./Exception');
const net = require('net');
const shttps = require('socks-proxy-agent');




//Config
dotenv.config()

const Domain = (process.env.HTTP_SSL ? "https://" : "http://") + process.env.DOMAIN_API;
const ApiToken = process.env.INSTAGRAM_API_TOKEN;
const UploadToken = process.env.UPLOAD_FILE_TOKEN;
const GetToken = process.env.GET_FILE_TOKEN;
const port = process.env.INSTAGRAM_PORT;
const ip = process.env.INSTAGRAM_IP;
const XDEBUG_SESSION = process.env.DEBUG == 'true' ? "XDEBUG_ECLIPSE" : "";


const LogTypeError = "Error";
const LogTypeFatalError = "Fatal Error";
const LogTypeWarning = "Warning";
const LogTypeInfo = "Info";




//Technical variables
let Instagrams = {};



/**
 * Init
 */
let __consoleLog = console.log;
let __consoleError = console.error;
console.log = (Message) => {
    let date = new Date();
    Message = `[${FormatNumber(date.getDate())}:${FormatNumber(date.getMonth())}:${FormatNumber(date.getFullYear())} ${FormatNumber(date.getHours())}:${FormatNumber(date.getMinutes())}:${FormatNumber(date.getSeconds())}] ${Message}`;
    __consoleLog(Message);
    Log(LogTypeInfo, Message);
};

console.error = (Message, ExceptionErr = null) => {
    let date = new Date();
    Message = `[${FormatNumber(date.getDate())}:${FormatNumber(date.getMonth())}:${FormatNumber(date.getFullYear())} ${FormatNumber(date.getHours())}:${FormatNumber(date.getMinutes())}:${FormatNumber(date.getSeconds())}] ${Message}`;
    __consoleError(Message + (ExceptionErr ? ` Error: ${typeof ExceptionErr === 'object' ? JSON.stringify(ExceptionErr) : ExceptionErr}` : ''));
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
            let body = null;

            request.on('data', async function(data) {
                if (body == null)
                    body = data;
                else
                    body = await Buffer.concat([body, data]);
            });

            request.on('end', function() {
                OnHttp(body.toString('utf8'), request, response).catch((e) => {
                    if (!e)
                        response.end(JSON.stringify({ status: "error", error: e.toString() }));
                });
            });
        }
    }).listen(port, ip, () => {});

    console.log(`Server is running at ${ip}:${port}`);
    console.log("Initialization complete");

    AsyncInstagramsInit();
}
Main();





/**
 * Methods
 */

async function AsyncInstagramsInit() {
    let _instagrams = await GetInstagrams();
    for (let i = 0; i < _instagrams.length; i++) {

        while (true) {
            let InitCount = 0;
            for (const [key, value] of Object.entries(Instagrams))
                if (!value.is_init)
                    InitCount++;

            if (InitCount < 2)
                break;

            await SleepMS(500);
        }

        InstagramInit(_instagrams[i], false).catch((e) => {
            console.error(e.toString());
        });
    }
}


async function InstagramInit(Instagram, SmsCode = true) {
    if (!Instagram) {
        console.error(`Initialization error. Instagram is empty!`);
        throw new Exception(`Instagram is empty`, 2001);
    }
    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Initialization start...`);

    if (Instagrams[Instagram.instagram_id]) {
        if (!Instagrams[Instagram.instagram_id].is_init) {
            console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Instagram already in the process of initialization!`);

            if (Instagrams[Instagram.instagram_id].two_factor)
                return "TwoFactorWait";

            console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Waiting for initialization to complete...`);

            while (true) {
                if (!Instagrams[Instagram.instagram_id])
                    throw new Exception(`Instagram initialization error`, 2002);

                if (Instagrams[Instagram.instagram_id].is_init)
                    return;

                await SleepMS(2000);
            }
        } else
            console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Initialization error. Instagram already initialized!`);
        return;
    }


    for (const [key, value] of Object.entries(Instagrams)) {
        if (value.login == Instagram.login)
            throw new Exception(`Instagram with this username has already been initialized! Initialized: UserId: ${value.user_id} InstId: ${value.instagram_id} Login: ${value.login}. Initialize: UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login}`);
    }


    Instagram.is_init = false;
    Instagrams[Instagram.instagram_id] = Instagram;
    EventSessionInvalid(Instagram.instagram_id, false);

    try {
        console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Initialization...`);

        Instagram.ig = withFbnsAndRealtime(new IgApiClient());
        await Instagram.ig.state.generateDevice(Instagram.login);

        if (Instagram.proxy) {
            console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} Use proxy: ${Instagram.proxy.host_name}:${Instagram.proxy.protocol}`);
            Instagram.ig.request.defaults.agentClass = shttps;
            Instagram.ig.request.defaults.agentOptions = {
                hostname: Instagram.proxy.host,
                port: Instagram.proxy.port,
                protocol: 'socks:'
            };
        }

        //СУПЕР МЕГА КОСТЫЛЬ. Я ЗАДРАЛСЯ!!!!
        if (Instagram.session) {
            try {
                let ig_temp = withFbnsAndRealtime(new IgApiClient());
                await ig_temp.state.generateDevice(Instagram.login);
                if (Instagram.proxy) {
                    ig_temp.request.defaults.agentClass = shttps;
                    ig_temp.request.defaults.agentOptions = {
                        hostname: Instagram.proxy.host,
                        port: Instagram.proxy.port,
                        protocol: 'socks:'
                    };
                }
                await ig_temp.state.deserialize(JSON.parse(Instagram.session));
                await ig_temp.user.getIdByUsername(Instagram.login);
                delete ig_temp;

                await Instagram.ig.state.deserialize(JSON.parse(Instagram.session));
                await InstagramLogged(Instagram);
            } catch (error) {
                if (error.name != "IgLoginRequiredError" && error.name != "IgUserHasLoggedOutError")
                    throw error;
            }
        }

        if (!Instagram.is_init) {
            console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Auth...`);
            try {
                await Instagram.ig.account.login(Instagram.login, Instagram.password);
                await InstagramLogged(Instagram);
            } catch (error) {
                if (error.name != 'IgLoginTwoFactorRequiredError' || !SmsCode)
                    throw error;

                await EventTwoFactor(Instagram.instagram_id);

                Instagram.two_factor = true;
                Instagram.verificationMethod = (error.response.body.two_factor_info.totp_two_factor_on ? '0' : '1');
                Instagram.twoFactorIdentifier = error.response.body.two_factor_info.two_factor_identifier;

                console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Two factor Wait...`);
                return "TwoFactorWait";
            }
        }
    } catch (error) {
        EventSessionInvalid(Instagram.instagram_id);
        try { await InstagramClose(Instagram.instagram_id); } catch (err) {}
        console.error(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Initialization error.`, error);
        throw new Exception(`Instagram initialization error`, 2002);
    }
}


async function TwoFactorCode(InstagramId, Code) {
    let Instagram = Instagrams[InstagramId];
    if (!Instagram)
        throw new Exception("Instagram not found", 404);

    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Entering a two-factor code`);

    if (!Instagram.two_factor)
        throw new Exception("TwoFactors are not required", 2004);

    if (Instagram.is_init)
        throw new Exception("Already initialized", 2005);


    // Use the code to finish the login process
    await Instagram.ig.account.twoFactorLogin({
        username: Instagram.login,
        verificationCode: Code.toString(),
        twoFactorIdentifier: Instagram.twoFactorIdentifier,
        verificationMethod: Instagram.verificationMethod, // '1' = SMS (default), '0' = TOTP (google auth for example)
    });

    await InstagramLogged(Instagram);
}


async function InstagramLogged(Instagram) {
    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Logged!`);
    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Connecting...`);
    Instagram.is_init = true;

    let serialized = await Instagram.ig.state.serialize();
    delete serialized.constants;
    await UpdateSession(Instagram.instagram_id, JSON.stringify(serialized));

    Instagram.instagram_user_id = await Instagram.ig.user.getIdByUsername(Instagram.login);

    Instagram.ig.realtime.on('message', (data) => { OnMessage(Instagram, data); });
    Instagram.ig.fbns.on('push', (data) => { OnPush(Instagram, data); });

    await Instagram.ig.realtime.connect(Object.assign({
        graphQlSubs: [
            GraphQLSubscriptions.getAppPresenceSubscription(),
            GraphQLSubscriptions.getZeroProvisionSubscription(Instagram.ig.state.phoneId),
            GraphQLSubscriptions.getDirectStatusSubscription(),
            GraphQLSubscriptions.getDirectTypingSubscription(Instagram.ig.state.cookieUserId),
            GraphQLSubscriptions.getAsyncAdSubscription(Instagram.ig.state.cookieUserId),
        ],
        skywalkerSubs: [
            SkywalkerSubscriptions.directSub(Instagram.ig.state.cookieUserId),
            SkywalkerSubscriptions.liveSub(Instagram.ig.state.cookieUserId),
        ],
        irisData: await Instagram.ig.feed.directInbox().request(),
        connectOverrides: {},
    }, (Instagram.proxy ? {
        socksOptions: {
            type: 5,
            host: Instagram.proxy.host,
            port: Instagram.proxy.port

        }
    } : {})));

    await Instagram.ig.fbns.connect(Instagram.proxy ? {
        socksOptions: {
            type: 5,
            host: Instagram.proxy.host,
            port: Instagram.proxy.port
        }
    } : {});

    await EventConnected(Instagram.instagram_id);
    await SyncDialogues(Instagram);
}


async function EventNotActive(InstagramId) {
    console.log(`InstId: ${InstagramId} >> Not active`);
    await RunEventStatus("NotActive", { "instagram_id": InstagramId });
}


async function EventConnected(InstagramId) {
    console.log(`InstId: ${InstagramId} >> Connected`);
    await RunEventStatus("Connected", { "instagram_id": InstagramId });
}


async function EventTwoFactor(InstagramId) {
    await RunEventStatus("TwoFactor", { "instagram_id": InstagramId });
}


async function EventSessionInvalid(InstagramId, Echo = true) {
    if (Echo)
        console.log(`InstId: ${InstagramId} >> Session invalid`);
    await RunEventStatus("SessionInvalid", { "instagram_id": InstagramId });
}


async function RunEventStatus(Event, Data) {
    await RequestPost(`${Domain}/event/instagram-sdk/status`, Object.assign({ "event": Event, "token": ApiToken }, Data));
}


async function UpdateSession(InstagramId, Session) {
    await RequestPost(`${Domain}/update/instagram-sdk/session`, { "instagram_id": InstagramId, "session": Session });
}


async function GetInstagrams() {
    let __error;
    try {
        let Response = JSON.parse(await RequestGet(`${Domain}/get/instagram-sdk/instagrams`, true));

        if (Response.status == "ok")
            return Response.instagrams;
    } catch (error) { __error = error; }

    console.log(`Get Instagram accounts error. Error: ${Response ? JSON.stringify(Response) : ''} ${__error ? __error.toString() : ''}`);
    throw new Exception(`Get Instagram accounts error`, 2003);
}


async function SyncDialogues(Instagram) {
    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Sync dialogues...`);

    let items = await Instagram.ig.feed.directInbox().items();
    for (const [key, value] of Object.entries(items)) {
        for (const [keymess, valuemess] of Object.entries(items[key].items)) {
            items[key].items[keymess].is_me = items[key].items[keymess].user_id == items[key].inviter.pk;
            items[key].items[keymess].timestamp = items[key].items[keymess].timestamp / 1000000;
        }
    }

    RequestPost(`${Domain}/put/instagram-sdk/sync-dialogues`, {
        user_id: Instagram.user_id,
        instagram_id: Instagram.instagram_id,
        items: items
    });
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


async function Log(Type, Message, error) {
    await new Promise((resolve, reject) => {
        let client = new net.Socket();

        client.on('error', function(ex) {
            resolve();
        });

        client.connect(1436, "localhost", async function() {
            MessObj = {
                Source: 'Instagram',
                Type: Type,
                Message: Message,
                UserId: -3
            };

            if (error) {
                let StackTrace = {};
                Error.captureStackTrace(StackTrace);

                if (error instanceof Exception)
                    MessObj.Data = ("\nMessage: " + error.Message + "\nCode: " + error.Code + "\nStack: " + error.Stack);
                else
                    MessObj.Data = ("\nMessage: " + (typeof error === 'object' ? JSON.stringify(error) : String(error)) + "\nStack: " + (error.stack ? error.stack : StackTrace.stack));
            }

            await client.write(JSON.stringify(MessObj));
            resolve();
        });
    });
}


async function SleepMS(time = 15000) {
    await new Promise((resolve, reject) => {
        setTimeout(() => { resolve() }, time);
    });
}


function FormatNumber(Value) {
    return Value < 10 ? `0${Value}` : Value;
}


async function InstagramClose(InstagramId) {
    let Instagram = Instagrams[InstagramId];
    if (!Instagram)
        throw new Exception("Instagram not found", 404);

    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Closing...`);

    try {
        await Instagram.ig.account.logout();
        await Instagram.ig.fbns.disconnect();
    } catch (error) {}
    await EventNotActive(InstagramId);
    delete Instagrams[InstagramId];
}


async function OnMessage(Instagram, Message) {
    try {
        if (!Message.mutation_token || Message.message.op != "add")
            return;

        let UserInfo;
        Message.message.timestamp = Message.message.timestamp / 1000000;
        Message.message.is_me = (Instagram.instagram_user_id == Message.message.user_id);

        if (Message.message.is_me)
            UserInfo = await Instagram.ig.user.info((await GetUserByThreadId(Instagram, Message.message.thread_id)).user_id);
        else
            UserInfo = await Instagram.ig.user.info(Message.message.user_id);

        console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> New message. UserName: ${UserInfo.username}`);

        RequestPost(`${Domain}/put/instagram-sdk/instagrams/message`, {
            "full_name": UserInfo.full_name,
            "user_id": Instagram.user_id,
            "login": UserInfo.username,
            "instagram_id": Instagram.instagram_id,
            "message": Message,
            "hd_profile_pic_url_info_url": UserInfo.hd_profile_pic_url_info.url
        });
    } catch (error) {
        console.error("Ошибка при обработке нового сообщения", error);
    }
}


async function GetAllThreads(Instagram, Force = false) {
    if (!Instagram.Threads)
        Instagram.Threads = { Time: 0 };

    if ((Instagram.Threads.Time + 60) < Unix() || Force) {
        Instagram.Threads = {
            Inbox: await Instagram.ig.feed.directInbox().items(),
            Time: Unix()
        }
    }

    return Instagram.Threads.Inbox;
}


//old
async function GetUserByThreadId(Instagram, ThreadId, Force = false) {
    if (!Instagram.UsersData)
        Instagram.UsersData = {};

    if (!Instagram.UsersData[ThreadId] || Force) {
        let Threads = await GetAllThreads(Instagram, true);
        for (let i = 0; i < Threads.length; i++) {
            if (Threads[i].thread_id == ThreadId) {
                Instagram.UsersData[ThreadId] = {
                    username: Threads[i].users[0].username,
                    full_name: Threads[i].users[0].full_name,
                    user_id: Threads[i].users[0].pk,
                    timestamp: 0
                };
                break;
            }
        }
        if (!Instagram.UsersData[ThreadId])
            throw new Exception("GetUserByThreadId Not Found", 404);
    }

    return Instagram.UsersData[ThreadId];
}


function Unix() {
    return Date.now() / 1000;
}


//old
async function GetThreadStatus(InstagramId, ThreadLogin) {
    let Instagram = Instagrams[InstagramId];
    if (!Instagram)
        throw new Exception("Instagram not found", 404);

    if (!Instagram.ThreadStatuses)
        Instagram.ThreadStatuses = {};

    if (!Instagram.ThreadStatuses[ThreadLogin] || (Instagram.ThreadStatuses[ThreadLogin] && (Instagram.ThreadStatuses[ThreadLogin].Time + 300) < Unix())) {
        let User = await GetUserByThreadId(Instagram, await GetThreadIdByLogin(Instagram, ThreadLogin), true);
        Instagram.ThreadStatuses[ThreadLogin] = {
            Data: {
                Login: ThreadLogin,
                IsOnline: (((User.timestamp / 1000) / 1000) + 300) > Unix()
            },
            Time: Unix()
        };
    }

    return Instagram.ThreadStatuses[ThreadLogin].Data;
}


//old
async function GetThreadIdByLogin(Instagram, Login) {
    if (!Instagram.UsersData)
        Instagram.UsersData = {};

    let Threads = await GetAllThreads(Instagram);
    for (let i = 0; i < Threads.length; i++)
        if (Threads[i].users[0].username == Login)
            return Threads[i].thread_id

    throw new Exception("GetThreadIdByLogin Not Found", 404);
}


async function OnPush(Instagram, Push) {
    switch (Push.pushCategory) {
        case "comment":
            OnComment(Instagram, Push);
            break;

        case "new_follower":
            OnNewSubscriber(Instagram, Push);
            break;
    }
}


async function OnNewSubscriber(Instagram, Push) {
    if (!Instagram.subscriber_tracking)
        return;

    let User = await Instagram.ig.user.info(await Instagram.ig.user.getIdByUsername(Push.actionParams.username));

    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> New subscriber. UserName: ${Push.actionParams.username}`);

    RequestPost(`${Domain}/event/instagram-sdk/new-subscriber`, {
        user_id: Instagram.user_id,
        instagram_id: Instagram.instagram_id,
        username: Push.actionParams.username,
        full_name: User.full_name,
        hd_profile_pic_url_info_url: User.hd_profile_pic_url_info.url
    });
}


async function FindComment(CommentId, Comments) {
    for (const [key, value] of Object.entries(Comments))
        if (value.pk == CommentId)
            return value;

    throw new Exception("Comment Not Found", 404);
}


async function OnComment(Instagram, Push) {
    if (!Instagram.comment_tracking)
        return;

    let Comment = null;
    let Media = await Instagram.ig.media.info(Push.actionParams.media_id);
    if (!Media)
        return;

    let Url = "https://www.instagram.com/p/" + Media.items[0].code;

    try {
        Comment = await FindComment(Push.actionParams.target_comment_id, Media.items[0].preview_comments);
    } catch (error) {
        if (error.code != 404)
            throw error;

        Media = await Instagram.ig.feed.mediaComments(Push.actionParams.media_id).request();
        Comment = await FindComment(Push.actionParams.target_comment_id, Media.comments);
    }

    if (!Comment || Comment.user.username == Instagram.login)
        return;

    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> New comment. UserName: ${Comment.user.username}`);

    RequestPost(`${Domain}/event/instagram-sdk/comment`, {
        "full_name": Comment.user.full_name,
        "user_id": Instagram.user_id,
        "username": Comment.user.username,
        "instagram_id": Instagram.instagram_id,
        "comment": Comment.text,
        "post_url": Url,
        "hd_profile_pic_url_info_url": Comment.user.profile_pic_url
    });
}


async function SendMessage(InstagramId, ToLogin, Type, Data) {
    let Instagram = Instagrams[InstagramId];
    if (!Instagram)
        throw new Exception("Instagram not found", 404);

    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Send message. By ${ToLogin}`);

    let Out;
    try {
        const thread = await Instagram.ig.entity.directThread([(await Instagram.ig.user.getIdByUsername(ToLogin)).toString()]);
        switch (Type) {
            case "text":
                Out = await thread.broadcastText(Data);
                break;

            case "img":
                Out = await thread.broadcastPhoto({
                    file: Buffer.from(Data.img, 'base64')
                });
                break;

            case "video":
                Out = await thread.broadcastVideo({
                    video: Buffer.from(Data, 'base64')
                });
                break;

            default:
                throw new Exception('Type not supported');
        }
    } catch (error) {
        console.error(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Sending error. By ${ToLogin}. Error: ${error.toString()}`);
        throw error;
    }

    console.log(`UserId: ${Instagram.user_id} InstId: ${Instagram.instagram_id} Login: ${Instagram.login} >> Sent! By ${ToLogin}`);
    return Out;
}



/**
 * HTTP
 */
async function OnHttp(data, request, response) {
    let out = {};

    try {
        data = JSON.parse(data);

        switch (data.command) {
            case "InstagramInit":
                try {
                    if (await InstagramInit(data.instagram))
                        out.InstagramInit = "TwoFactorWait";
                    else
                        out.InstagramInit = "ok";
                } catch (error) {
                    out.error = error.message;
                    out.stack = error.stack;
                    out.code = error.code;
                }
                break;

            case "InstagramClose":
                try {
                    await InstagramClose(data.instagram_id);
                    out.InstagramClose = "ok";
                } catch (error) {
                    out.error = error.message;
                    out.stack = error.stack;
                    out.code = error.code;
                }
                break;

            case "GetThreadStatus":
                try {
                    out.GetThreadStatus = await GetThreadStatus(data.instagram_id, data.thread_login);
                } catch (error) {
                    out.error = error.message;
                    out.stack = error.stack;
                    out.code = error.code;
                }
                break;

            case "TwoFactorCode":
                try {
                    await TwoFactorCode(data.instagram_id, data.code);
                    out.TwoFactorCode = "ok";
                } catch (error) {
                    out.error = error.message;
                    out.stack = error.stack;
                    out.code = error.code;
                }
                break;

            case "SendMessage":
                try {
                    out.SendMessage = await SendMessage(data.instagram_id, data.tologin, data.type, data.data);
                } catch (error) {
                    out.error = error.message;
                    out.stack = error.stack;
                    out.code = error.code;
                }
                break;

            default:
                out.error = "Unknown command";
                out.code = 404;
                break;
        }
    } catch (error) {
        out.error = "Internal error";
        out.code = 500;
    }

    if (out.error) {
        out.status = "error";

        console.error(`Error processing request`, out);
    } else
        out.status = "ok";

    response.end(JSON.stringify(out));
}