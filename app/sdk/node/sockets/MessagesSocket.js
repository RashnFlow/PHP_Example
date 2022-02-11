const WebSocket         = require('ws');
const { createServer }  = require('http');
const { readFileSync }  = require('fs');
const dotenv            = require('dotenv');
const request           = require('request');
const Exception         = require('./Exception');
const net               = require('net');


//Config
dotenv.config()
const port      = process.env.MESSAGE_SOCKET_PORT;
const ip        = process.env.SERVER_IP;
const Domain    = (process.env.HTTP_SSL ? "https://" : "http://") + process.env.DOMAIN_API;
const localPort = (process.env.MESSAGE_SOCKET.split(":"))[2];


const LogTypeError          = "Error";
const LogTypeFatalError     = "Fatal Error";
const LogTypeWarning        = "Warning";
const LogTypeInfo           = "Info";



//Technical variables
var wss;
var wses = [];


/**
 * FUNCTIONS
 */
async function onConnect(ws)
{
    ws.on('message', function (message) {
        onMessage(ws, message);
    });

    ws.on('close', function () {
        for(var i = 0; i < wses.length; i++)
        {
            if(ws == wses[i])
            {
                wses.splice(i, 1);
                return;
            }
        }
    });

    wses.push(ws);
}


async function onMessage(ws, message) {
    try
    {
        message = JSON.parse(message.toString());
        Uri = "";
        switch(message.command)
        {
            case "send_message":
                Uri = "/send/message/";
                break;

            case "get_dialog_status":
                Uri = "/get/dialog/status/";
                break;
        }

        if(Uri)
            ws.send(JSON.stringify(Object.assign((await SendServer(ws.token, Uri, message)), {"command": message.command, "request_uid": message.request_uid})));
        else
            ws.send(JSON.stringify({
                "command": message.command,
                "status": "error",
                "error": "Command not found",
                "code": 404
            }));
    }
    catch(error)
    {
        Log(LogTypeFatalError, "Ошибка при обработке запроса клиента", error);
    }
}


function OnServerMessage(message) {
    message = JSON.parse(message.toString());

    for(var i = 0; i < wses.length; i++)
        if(message.UserId == wses[i].user_id)
            wses[i].send(JSON.stringify(message.Data));
}


async function Log(Type, Message, error) {
    var client = new net.Socket();

    client.on('error', function(ex) {});

    client.connect(1436, "localhost", function() {
        if(error instanceof Exception)
            client.write(JSON.stringify({Type: Type, Message: Message, Data: ("\nMessage: " + error.Message + "\nCode: " + error.Code + "\nStack: " + error.Stack), UserId: -101}));
        else
            client.write(JSON.stringify({Type: Type, Message: Message, Data: ("\nMessage: " + String(error) + "\nStack: " + (new Error().stack)), UserId: -101}));
    });
}


async function SendServer(Token, Uri, Data = null, Encoding = 'UTF-8') {
    return await new Promise(function(resolve, reject) {
        if(Token) {
            if(Uri.indexOf('?') > -1)
                Uri += `&`;
            else
                Uri += `?`;

            Uri += `token=${Token}`;
        }
        request.post({
            encoding: Encoding,
            url: Domain + Uri,
            headers: {
                'content-type': 'application/json',
                "Authorization": `Bearer ${Token}`
            },
            body: Data != null ? JSON.stringify(Data) : undefined
        }, function(err, httpResponse, body) {
            resolve(body ? JSON.parse(body) : {status: "error", error: "Unknown error"});
        });
    });
}


async function Start() {
    var WebSocketServer = createServer();
    // if(process.env.HTTP_SSL)
    //     WebSocketServer = createServer({
    //         cert: await readFileSync(process.env.LOCAL_CERT),
    //         key: await readFileSync(process.env.LOCAL_PK)
    //     });

    wss = new WebSocket.Server({ noServer: true });
    wss.on('connection', onConnect);

    WebSocketServer.on('upgrade', async function (request, socket, head) {
        try
        {
            var token = request.url.match(/token=\w+/);
            if(!token)
                return;
            token = token[0].replace("token=", "");

            var Response = await SendServer(token, "/get/user");
            if(Response && Response.status == "ok")
            {
                wss.handleUpgrade(request, socket, head, function (ws) {
                    ws.token = token;
                    ws.user_id = Response.user.user_id;
                    wss.emit('connection', ws, request);
                });
            }
        }
        catch(error)
        {
            Log(LogTypeFatalError, "Ошибка при подключении клиента", error);
        }
    });

    WebSocketServer.listen(port, ip);

    createServer(function(request, response) {
        if (request.method == 'POST') {
            var body = null;

            request.on('data', async function(data) {
                if (body == null)
                    body = data;
                else
                    body = await Buffer.concat([body, data]);
            });

            request.on('end', async function() {
                OnServerMessage(body);
                response.end(JSON.stringify({status: "ok"}));
            });
        }
    }).listen(localPort, "localhost");

    console.log(`GLOBAL: Server is running at ${ip}:${port}`);
    console.log(`LOCAL: Server is running at localhost:${localPort}`);
    console.log("Initialization complete");
}

Start();