class Exception {
    message;
    code;
    stack = new Error().stack;

    constructor(message, code = -1) {
        this.message    = message;
        this.code       = code;
    }
}

module.exports = Exception;