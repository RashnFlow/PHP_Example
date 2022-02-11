class Exception {
    message;
    code;
    stack = new Error().stack;

    constructor(message, code = -1) {
        this.message    = message;
        this.code       = code;
    }

    toString() {
        return `${this.message} code: ${this.code}`;
    }
}

module.exports = Exception;