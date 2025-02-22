! function(e, t) { "object" == typeof exports && "undefined" != typeof module ? module.exports = t() : "function" == typeof define && define.amd ? define(t) : (e = e || self).HubApi = t() }(this, function() {
    "use strict";
    class e {
        static byteLength(t) { const [s, r] = e._getLengths(t); return e._byteLength(s, r) }
        static decode(t) {
            e._initRevLookup();
            const [s, r] = e._getLengths(t), n = new Uint8Array(e._byteLength(s, r));
            let i = 0;
            const o = r > 0 ? s - 4 : s;
            let a = 0;
            for (; a < o; a += 4) {
                const s = e._revLookup[t.charCodeAt(a)] << 18 | e._revLookup[t.charCodeAt(a + 1)] << 12 | e._revLookup[t.charCodeAt(a + 2)] << 6 | e._revLookup[t.charCodeAt(a + 3)];
                n[i++] = s >> 16 & 255, n[i++] = s >> 8 & 255, n[i++] = 255 & s
            }
            if (2 === r) {
                const s = e._revLookup[t.charCodeAt(a)] << 2 | e._revLookup[t.charCodeAt(a + 1)] >> 4;
                n[i++] = 255 & s
            }
            if (1 === r) {
                const s = e._revLookup[t.charCodeAt(a)] << 10 | e._revLookup[t.charCodeAt(a + 1)] << 4 | e._revLookup[t.charCodeAt(a + 2)] >> 2;
                n[i++] = s >> 8 & 255, n[i] = 255 & s
            }
            return n
        }
        static encode(t) {
            const s = t.length,
                r = s % 3,
                n = [];
            for (let i = 0, o = s - r; i < o; i += 16383) n.push(e._encodeChunk(t, i, i + 16383 > o ? o : i + 16383));
            if (1 === r) {
                const r = t[s - 1];
                n.push(e._lookup[r >> 2] + e._lookup[r << 4 & 63] + "==")
            } else if (2 === r) {
                const r = (t[s - 2] << 8) + t[s - 1];
                n.push(e._lookup[r >> 10] + e._lookup[r >> 4 & 63] + e._lookup[r << 2 & 63] + "=")
            }
            return n.join("")
        }
        static encodeUrl(t) { return e.encode(t).replace(/\//g, "_").replace(/\+/g, "-").replace(/=/g, ".") }
        static decodeUrl(t) { return e.decode(t.replace(/_/g, "/").replace(/-/g, "+").replace(/\./g, "=")) }
        static _initRevLookup() {
            if (0 === e._revLookup.length) {
                e._revLookup = [];
                for (let t = 0, s = e._lookup.length; t < s; t++) e._revLookup[e._lookup.charCodeAt(t)] = t;
                e._revLookup["-".charCodeAt(0)] = 62, e._revLookup["_".charCodeAt(0)] = 63
            }
        }
        static _getLengths(e) { const t = e.length; if (t % 4 > 0) throw new Error("Invalid string. Length must be a multiple of 4"); let s = e.indexOf("="); return -1 === s && (s = t), [s, s === t ? 0 : 4 - s % 4] }
        static _byteLength(e, t) { return 3 * (e + t) / 4 - t }
        static _tripletToBase64(t) { return e._lookup[t >> 18 & 63] + e._lookup[t >> 12 & 63] + e._lookup[t >> 6 & 63] + e._lookup[63 & t] }
        static _encodeChunk(t, s, r) {
            const n = [];
            for (let i = s; i < r; i += 3) {
                const s = (t[i] << 16 & 16711680) + (t[i + 1] << 8 & 65280) + (255 & t[i + 2]);
                n.push(e._tripletToBase64(s))
            }
            return n.join("")
        }
    }
    var t, s, r, n, i, o, a, c, l, h;
    e._lookup = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", e._revLookup = [],
        function(e) { e[e.UINT8_ARRAY = 0] = "UINT8_ARRAY" }(t || (t = {}));
    class d {
        static stringify(e) { return JSON.stringify(e, d._jsonifyType) }
        static parse(e) { return JSON.parse(e, d._parseType) }
        static _parseType(s, r) {
            if (r && r.hasOwnProperty && r.hasOwnProperty(d.TYPE_SYMBOL) && r.hasOwnProperty(d.VALUE_SYMBOL)) switch (r[d.TYPE_SYMBOL]) {
                case t.UINT8_ARRAY:
                    return e.decode(r[d.VALUE_SYMBOL])
            }
            return r
        }
        static _jsonifyType(s, r) { return r instanceof Uint8Array ? d._typedObject(t.UINT8_ARRAY, e.encode(r)) : r }
        static _typedObject(e, t) { const s = {}; return s[d.TYPE_SYMBOL] = e, s[d.VALUE_SYMBOL] = t, s }
    }
    d.TYPE_SYMBOL = "__", d.VALUE_SYMBOL = "v";
    class u { static generateRandomId() { const e = new Uint32Array(1); return crypto.getRandomValues(e), e[0] } }! function(e) { e.HTTP_POST = "http-post", e.HTTP_GET = "http-get", e.POST_MESSAGE = "post-message" }(s || (s = {})),
    function(e) { e.OK = "ok", e.ERROR = "error" }(r || (r = {}));
    class _ {
        constructor(e = !0) { this._store = e ? window.sessionStorage : null, this._validIds = new Map, e && this._restoreIds() }
        static _decodeIds(e) {
            const t = d.parse(e),
                s = new Map;
            for (const e of Object.keys(t)) {
                const r = parseInt(e, 10);
                s.set(isNaN(r) ? e : r, t[e])
            }
            return s
        }
        has(e) { return this._validIds.has(e) }
        getCommand(e) { const t = this._validIds.get(e); return t ? t[0] : null }
        getState(e) { const t = this._validIds.get(e); return t ? t[1] : null }
        add(e, t, s = null) { this._validIds.set(e, [t, s]), this._storeIds() }
        remove(e) { this._validIds.delete(e), this._storeIds() }
        clear() { this._validIds.clear(), this._store && this._store.removeItem(_.KEY) }
        _encodeIds() { const e = Object.create(null); for (const [t, s] of this._validIds) e[t] = s; return d.stringify(e) }
        _restoreIds() {
            const e = this._store.getItem(_.KEY);
            e && (this._validIds = _._decodeIds(e))
        }
        _storeIds() { this._store && this._store.setItem(_.KEY, this._encodeIds()) }
    }
    _.KEY = "rpcRequests";
    class p {
        static receiveRedirectCommand(e) {
            const t = new URL(e.href);
            if (!document.referrer) return null;
            const r = new URL(document.referrer),
                n = new URLSearchParams(t.search),
                i = new URLSearchParams(t.hash.substring(1));
            if (!i.has("id")) return null;
            const o = parseInt(i.get("id"), 10);
            if (i.delete("id"), n.set(p.URL_SEARCHPARAM_NAME, o.toString()), !i.has("command")) return null;
            const a = i.get("command");
            if (i.delete("command"), !i.has("returnURL")) return null;
            const c = i.get("returnURL");
            i.delete("returnURL");
            let l = s.HTTP_GET;
            if (i.has("responseMethod") && (l = i.get("responseMethod"), i.delete("responseMethod"), !Object.values(s).includes(l))) throw new Error("Invalid ResponseMethod");
            if (!(l === s.POST_MESSAGE && (window.opener || window.parent)) && new URL(c).origin !== r.origin) return null;
            let h = [];
            if (i.has("args")) try { h = d.parse(i.get("args")) } catch (e) {}
            return h = Array.isArray(h) ? h : [], i.delete("args"), t.search = n.toString(), this._setUrlFragment(t, i), history.replaceState(history.state, "", t.href), { origin: r.origin, data: { id: o, command: a, args: h }, returnURL: c, responseMethod: l, source: l === s.POST_MESSAGE ? window.opener || window.parent : null }
        }
        static receiveRedirectResponse(e) {
            const t = new URL(e.href);
            if (!document.referrer) return null;
            const s = new URL(document.referrer),
                n = new URLSearchParams(t.search),
                i = new URLSearchParams(t.hash.substring(1));
            if (!i.has("id")) return null;
            const o = parseInt(i.get("id"), 10);
            if (i.delete("id"), n.set(p.URL_SEARCHPARAM_NAME, o.toString()), !i.has("status")) return null;
            const a = i.get("status") === r.OK ? r.OK : r.ERROR;
            if (i.delete("status"), !i.has("result")) return null;
            const c = d.parse(i.get("result"));
            return i.delete("result"), t.search = n.toString(), this._setUrlFragment(t, i), history.replaceState(history.state, "", t.href), { origin: s.origin, data: { id: o, status: a, result: c } }
        }
        static prepareRedirectReply(e, t, s) {
            const r = new URL(e.returnURL),
                n = new URLSearchParams(r.hash.substring(1));
            return n.set("id", e.id.toString()), n.set("status", t), n.set("result", d.stringify(s)), r.hash = n.toString(), r.href
        }
        static prepareRedirectInvocation(e, t, s, r, n, i) {
            const o = new URL(e),
                a = new URLSearchParams(o.hash.substring(1));
            return a.set("id", t.toString()), a.set("returnURL", s), a.set("command", r), a.set("responseMethod", i), Array.isArray(n) && a.set("args", d.stringify(n)), o.hash = a.toString(), o.href
        }
        static _setUrlFragment(e, t) { t.toString().endsWith("=") ? e.hash = t.toString().slice(0, -1) : e.hash = t.toString() }
    }
    p.URL_SEARCHPARAM_NAME = "rpcId";
    class g {
        constructor(e, t = !1) { this._allowedOrigin = e, this._waitingRequests = new _(t), this._responseHandlers = new Map, this._preserveRequests = !1 }
        onResponse(e, t, s) { this._responseHandlers.set(e, { resolve: t, reject: s }) }
        _receive(e) {
            if (!e.data || !e.data.status || !e.data.id || "*" !== this._allowedOrigin && e.origin !== this._allowedOrigin) return !1;
            const t = e.data,
                s = this._getCallback(t.id),
                n = this._waitingRequests.getState(t.id);
            if (s) {
                if (this._preserveRequests || (this._waitingRequests.remove(t.id), this._responseHandlers.delete(t.id)), console.debug("RpcClient RECEIVE", t), t.status === r.OK) s.resolve(t.result, t.id, n);
                else if (t.status === r.ERROR) {
                    const e = new Error(t.result.message);
                    t.result.stack && (e.stack = t.result.stack), t.result.name && (e.name = t.result.name), s.reject(e, t.id, n)
                }
                return !0
            }
            return console.warn("Unknown RPC response:", t), !1
        }
        _getCallback(e) { if (this._responseHandlers.has(e)) return this._responseHandlers.get(e); { const t = this._waitingRequests.getCommand(e); if (t) return this._responseHandlers.get(t) } }
    }
    class w extends g {
        constructor(e, t) { super(t), this._serverCloseCheckInterval = -1, this._target = e, this._connectionState = 0, this._receiveListener = this._receive.bind(this) }
        async init() { 2 !== this._connectionState && (await this._connect(), window.addEventListener("message", this._receiveListener), -1 === this._serverCloseCheckInterval && (this._serverCloseCheckInterval = window.setInterval(() => this._checkIfServerClosed(), 300))) }
        async call(e, ...t) { return this._call({ command: e, args: t, id: u.generateRandomId() }) }
        close() {
            this._connectionState = 0, window.removeEventListener("message", this._receiveListener), window.clearInterval(this._serverCloseCheckInterval), this._serverCloseCheckInterval = -1;
            for (const [e, { reject: t }] of this._responseHandlers) {
                const s = this._waitingRequests.getState(e);
                t("Connection was closed", "number" == typeof e ? e : void 0, s)
            }
            this._waitingRequests.clear(), this._responseHandlers.clear(), this._target && this._target.closed && (this._target = null)
        }
        _receive(e) { return e.source === this._target && super._receive(e) }
        async _call(e) { if (!this._target || this._target.closed) throw new Error("Connection was closed."); if (2 !== this._connectionState) throw new Error("Client is not connected, call init first"); return new Promise((t, s) => { this._responseHandlers.set(e.id, { resolve: t, reject: s }), this._waitingRequests.add(e.id, e.command), console.debug("RpcClient REQUEST", e.command, e.args), this._target.postMessage(e, this._allowedOrigin) }) }
        _connect() {
            if (2 !== this._connectionState) return this._connectionState = 1, new Promise((e, t) => {
                const s = t => {
                    const { source: n, origin: i, data: o } = t;
                    if (n === this._target && o.status === r.OK && "pong" === o.result && 1 === o.id && ("*" === this._allowedOrigin || i === this._allowedOrigin)) {
                        if (o.result.stack) {
                            const e = new Error(o.result.message);
                            e.stack = o.result.stack, o.result.name && (e.name = o.result.name), console.error(e)
                        }
                        window.removeEventListener("message", s), this._connectionState = 2, console.log("RpcClient: Connection established"), e(!0)
                    }
                };
                window.addEventListener("message", s);
                const n = () => {
                    if (2 !== this._connectionState) {
                        if (0 === this._connectionState || this._checkIfServerClosed()) return window.removeEventListener("message", s), void t(new Error("Connection was closed"));
                        try { this._target.postMessage({ command: "ping", id: 1 }, this._allowedOrigin) } catch (e) { console.error(`postMessage failed: ${e}`) }
                        window.setTimeout(n, 100)
                    }
                };
                window.setTimeout(n, 100)
            })
        }
        _checkIfServerClosed() { return !(this._target && !this._target.closed) && (this.close(), !0) }
    }
    class m extends g {
        constructor(e, t, s = !0) { super(t, !0), this._target = e, this._preserveRequests = s }
        async init() { const e = p.receiveRedirectResponse(window.location); if (e) return void this._receive(e); if (this._rejectOnBack()) return; const t = new URLSearchParams(window.location.search); if (t.has(p.URL_SEARCHPARAM_NAME)) { const e = window.sessionStorage.getItem(`response-${t.get(p.URL_SEARCHPARAM_NAME)}`); if (e) return void this._receive(d.parse(e), !1) } }
        close() {}
        call(e, t, r, ...n) {
            if (r && "boolean" != typeof r) {
                if ("object" == typeof r) {
                    if (r.responseMethod === s.POST_MESSAGE) {
                        if (!window.opener && !window.parent) throw new Error("Window has no opener or parent, responseMethod: ResponseMethod.POST_MESSAGE would fail.");
                        console.warn("Response will skip at least one rpc call, which will result in an unknown response.")
                    }
                    this._call(e, t, r, ...n)
                }
            } else "boolean" == typeof r && console.warn("RedirectRpcClient.call(string, string, boolean, any[]) is deprecated. Use RedirectRpcClient.call(string, string, CallOptions, any[]) with an appropriate CallOptions object instead."), this._call(e, t, { responseMethod: s.HTTP_GET, handleHistoryBack: !!r }, ...n)
        }
        callAndSaveLocalState(e, t, r, n = !1, ...i) { console.warn("RedirectRpcClient.callAndSaveLocalState() is deprecated. Use RedirectRpcClient.call() with an apropriate CallOptions object instead."), this._call(e, r, { responseMethod: s.HTTP_GET, state: t || void 0, handleHistoryBack: n }, ...i) }
        _receive(e, t = !0) { const s = super._receive(e); return s && t && window.sessionStorage.setItem(`response-${e.data.id}`, d.stringify(e)), s }
        _call(e, t, r, ...n) {
            const i = u.generateRandomId(),
                o = r.responseMethod || s.HTTP_GET,
                a = p.prepareRedirectInvocation(this._target, i, e, t, n, o);
            this._waitingRequests.add(i, t, r.state || null), r.handleHistoryBack && history.replaceState(Object.assign({}, history.state, { rpcBackRejectionId: i }), ""), console.debug("RpcClient REQUEST", t, n), window.location.href = a
        }
        _rejectOnBack() {
            if (!history.state || !history.state.rpcBackRejectionId) return !1;
            const e = history.state.rpcBackRejectionId;
            history.replaceState(Object.assign({}, history.state, { rpcBackRejectionId: null }), "");
            const t = this._getCallback(e),
                s = this._waitingRequests.getState(e);
            if (t) { this._preserveRequests || (this._waitingRequests.remove(e), this._responseHandlers.delete(e)), console.debug("RpcClient BACK"); const r = new Error("Request aborted"); return t.reject(r, e, s), !0 }
            return !1
        }
    }
    class R {
        constructor(e) { this._type = e }
        static getAllowedOrigin(e) { return new URL(e).origin }
        async request(e, t, s) { throw new Error("Not implemented") }
    }! function(e) { e[e.REDIRECT = 0] = "REDIRECT", e[e.POPUP = 1] = "POPUP", e[e.IFRAME = 2] = "IFRAME" }(n || (n = {}));
    class f extends R {
        constructor(e, t) { super(n.REDIRECT); const s = window.location; if (this._returnUrl = e || `${s.origin}${s.pathname}`, this._localState = t || {}, void 0 !== this._localState.__command) throw new Error("Invalid localState: Property '__command' is reserved") }
        static withLocalState(e) { return new f(void 0, e) }
        async request(e, t, s) {
            const r = R.getAllowedOrigin(e),
                n = new m(e, r);
            await n.init();
            const i = Object.assign({}, this._localState, { __command: t });
            n.callAndSaveLocalState(this._returnUrl, i, t, !0, ...await Promise.all(s))
        }
    }
    class A extends R {
        constructor(e = A.DEFAULT_FEATURES, t) { super(n.POPUP), this._popupFeatures = e, this._options = {...A.DEFAULT_OPTIONS, ...t } }
        async request(e, t, s) {
            const r = R.getAllowedOrigin(e),
                n = this.createPopup(e),
                i = this.appendOverlay(n),
                o = new w(n, r);
            try { return await o.init(), await o.call(t, ...await Promise.all(s)) } catch (e) { throw e } finally { this.removeOverlay(i), o.close(), n.close() }
        }
        createPopup(e) { const t = window.open(e, "FyfyAccounts", this._popupFeatures); if (!t) throw new Error("Failed to open popup"); return t }
        appendOverlay(e) {
            if (!this._options.overlay) return null;
            const t = document.createElement.bind(document),
                s = document.createTextNode.bind(document),
                r = (e, t) => e.appendChild(t),
                n = t("div");
            n.id = "fyfy-hub-overlay";
            const i = n.style;
            i.position = "fixed", i.top = "0", i.right = "0", i.bottom = "0", i.left = "0", i.background = "rgba(31, 35, 72, 0.8)", i.display = "flex", i.flexDirection = "column", i.alignItems = "center", i.justifyContent = "space-between", i.cursor = "pointer", i.color = "white", i.textAlign = "center", i.opacity = "0", i.transition = "opacity 0.6s ease", i.zIndex = "99999", n.addEventListener("click", () => e.focus()), r(n, t("div"));
            const o = t("div"),
                a = o.style;
            r(o, s("A popup has been opened,")), r(o, t("br")), r(o, s("click anywhere to bring it back to the front.")), a.fontFamily = 'Muli, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif', a.fontSize = "24px", a.fontWeight = "600", a.lineHeight = "40px", r(n, o);
            const c = t("img");
            c.src = 'data:image/svg+xml,<svg width="135" height="32" viewBox="0 0 135 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M35.6 14.5l-7.5-13A3 3 0 0025.5 0h-15a3 3 0 00-2.6 1.5l-7.5 13a3 3 0 000 3l7.5 13a3 3 0 002.6 1.5h15a3 3 0 002.6-1.5l7.5-13a3 3 0 000-3z" fill="url(%23hub-overlay-fyfy-logo)"/><path d="M62.25 6.5h3.26v19H63L52.75 12.25V25.5H49.5v-19H52l10.25 13.25V6.5zM72 25.5v-19h3.5v19H72zM97.75 6.5h2.75v19h-3V13.75L92.37 25.5h-2.25L85 13.75V25.5h-3v-19h2.75l6.5 14.88 6.5-14.88zM107 25.5v-19h3.5v19H107zM133.88 21.17a7.91 7.91 0 01-4.01 3.8c.16.38.94 1.44 1.52 2.05.59.6 1.2 1.23 1.98 1.86L131 30.75a15.91 15.91 0 01-4.45-5.02l-.8.02c-1.94 0-3.55-.4-4.95-1.18a7.79 7.79 0 01-3.2-3.4 11.68 11.68 0 01-1.1-5.17c0-2.03.37-3.69 1.12-5.17a7.9 7.9 0 013.2-3.4 9.8 9.8 0 014.93-1.18c1.9 0 3.55.4 4.94 1.18a7.79 7.79 0 013.2 3.4 11.23 11.23 0 011.1 5.17c0 2.03-.44 3.83-1.11 5.17zm-12.37.01a5.21 5.21 0 004.24 1.82 5.2 5.2 0 004.23-1.82c1.01-1.21 1.52-2.92 1.52-5.18 0-2.24-.5-4-1.52-5.2a5.23 5.23 0 00-4.23-1.8c-1.82 0-3.23.6-4.24 1.79-1 1.2-1.51 2.95-1.51 5.21s.5 3.97 1.51 5.18z" fill="white"/><defs><radialGradient id="hub-overlay-fyfy-logo" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="matrix(-35.9969 0 0 -32 36 32)"><stop stop-color="%23EC991C"/><stop offset="1" stop-color="%23E9B213"/></radialGradient></defs></svg>', c.style.marginBottom = "56px", r(n, c);
            const l = t("div"),
                h = l.style;
            return l.innerHTML = "&times;", h.position = "absolute", h.top = "8px", h.right = "8px", h.fontSize = "24px", h.lineHeight = "32px", h.fontWeight = "600", h.width = "32px", h.height = "32px", h.opacity = "0.8", l.addEventListener("click", () => e.close()), r(n, l), setTimeout(() => n.style.opacity = "1", 100), r(document.body, n)
        }
        removeOverlay(e) { e && (e.style.opacity = "0", setTimeout(() => document.body.removeChild(e), 400)) }
    }
    A.DEFAULT_FEATURES = "", A.DEFAULT_OPTIONS = { overlay: !0 };
    class S extends R {
        constructor() { super(n.IFRAME), this._iframe = null, this._client = null }
        async request(e, t, s) { if (this._iframe && this._iframe.src !== `${e}${S.IFRAME_PATH_SUFFIX}`) throw new Error("Hub iframe is already opened with another endpoint"); const r = R.getAllowedOrigin(e); if (this._iframe || (this._iframe = await this.createIFrame(e)), !this._iframe.contentWindow) throw new Error(`IFrame contentWindow is ${typeof this._iframe.contentWindow}`); return this._client || (this._client = new w(this._iframe.contentWindow, r), await this._client.init()), await this._client.call(t, ...await Promise.all(s)) }
        async createIFrame(e) {
            return new Promise((t, s) => {
                const r = document.createElement("iframe");
                r.name = "FyfyAccountsIFrame", r.style.display = "none", document.body.appendChild(r), r.src = `${e}${S.IFRAME_PATH_SUFFIX}`, r.onload = (() => t(r)), r.onerror = s
            })
        }
    }
    S.IFRAME_PATH_SUFFIX = "/iframe.html",
        function(e) { e.LIST = "list", e.LIST_CASHLINKS = "list-cashlinks", e.MIGRATE = "migrate", e.CHECKOUT = "checkout", e.SIGN_MESSAGE = "sign-message", e.SIGN_TRANSACTION = "sign-transaction", e.ONBOARD = "onboard", e.SIGNUP = "signup", e.LOGIN = "login", e.EXPORT = "export", e.CHANGE_PASSWORD = "change-password", e.LOGOUT = "logout", e.ADD_ADDRESS = "add-address", e.RENAME = "rename", e.CHOOSE_ADDRESS = "choose-address", e.CREATE_CASHLINK = "create-cashlink", e.MANAGE_CASHLINK = "manage-cashlink" }(i || (i = {})),
        function(e) { e[e.DIRECT = 0] = "DIRECT", e[e.OASIS = 1] = "OASIS" }(o || (o = {})),
        function(e) { e.FYFY = "fyfy", e.BTC = "btc", e.ETH = "eth" }(a || (a = {})),
        function(e) { e.NOT_FOUND = "NOT_FOUND", e.PAID = "PAID", e.UNDERPAID = "UNDERPAID", e.OVERPAID = "OVERPAID" }(c || (c = {})),
        function(e) { e[e.UNKNOWN = -1] = "UNKNOWN", e[e.UNCHARGED = 0] = "UNCHARGED", e[e.CHARGING = 1] = "CHARGING", e[e.UNCLAIMED = 2] = "UNCLAIMED", e[e.CLAIMING = 3] = "CLAIMING", e[e.CLAIMED = 4] = "CLAIMED" }(l || (l = {})),
        function(e) { e[e.UNSPECIFIED = 0] = "UNSPECIFIED", e[e.STANDARD = 1] = "STANDARD", e[e.CHRISTMAS = 2] = "CHRISTMAS", e[e.LUNAR_NEW_YEAR = 3] = "LUNAR_NEW_YEAR", e[e.GENERIC = 4] = "GENERIC", e[e.BIRTHDAY = 5] = "BIRTHDAY" }(h || (h = {}));
    class E {
        constructor(e = E.DEFAULT_ENDPOINT, t) { this._endpoint = e, this._defaultBehavior = t || new A(`left=${window.innerWidth/2-400},top=75,width=800,height=850,location=yes,dependent=yes`), this._checkoutDefaultBehavior = t || new A(`left=${window.innerWidth/2-400},top=50,width=800,height=895,location=yes,dependent=yes`), this._iframeBehavior = new S, this._redirectClient = new m("", R.getAllowedOrigin(this._endpoint)) }
        static get PaymentMethod() { return console.warn("PaymentMethod has been renamed to PaymentType. Access via HubApi.PaymentMethod will soon get disabled. Use HubApi.PaymentType instead."), o }
        static get DEFAULT_ENDPOINT() {
            const e = location.origin.split(".");
            switch (e.shift(), e.join(".")) {
                case "shop.fyfy.io":
                    return "https://hub.fyfy.io";
                case "fyfy-testnet.io":
                    return "https://hub.fyfy-testnet.io";
                default:
                    return "http://localhost:8080"
            }
        }
        checkRedirectResponse() { return this._redirectClient.init() }
        on(e, t, s) { this._redirectClient.onResponse(e, (e, s, r) => t(e, r), (e, t, r) => { s && s(e, r) }) }
        createCashlink(e, t = this._defaultBehavior) { return this._request(t, i.CREATE_CASHLINK, [e]) }
        manageCashlink(e, t = this._defaultBehavior) { return this._request(t, i.MANAGE_CASHLINK, [e]) }
        checkout(e, t = this._checkoutDefaultBehavior) { return this._request(t, i.CHECKOUT, [e]) }
        chooseAddress(e, t = this._defaultBehavior) { return this._request(t, i.CHOOSE_ADDRESS, [e]) }
        signTransaction(e, t = this._defaultBehavior) { return this._request(t, i.SIGN_TRANSACTION, [e]) }
        signMessage(e, t = this._defaultBehavior) { return this._request(t, i.SIGN_MESSAGE, [e]) }
        onboard(e, t = this._defaultBehavior) { return this._request(t, i.ONBOARD, [e]) }
        signup(e, t = this._defaultBehavior) { return this._request(t, i.SIGNUP, [e]) }
        login(e, t = this._defaultBehavior) { return this._request(t, i.LOGIN, [e]) }
        logout(e, t = this._defaultBehavior) { return this._request(t, i.LOGOUT, [e]) }
        export (e, t = this._defaultBehavior) { return this._request(t, i.EXPORT, [e]) }
        changePassword(e, t = this._defaultBehavior) { return this._request(t, i.CHANGE_PASSWORD, [e]) }
        addAddress(e, t = this._defaultBehavior) { return this._request(t, i.ADD_ADDRESS, [e]) }
        rename(e, t = this._defaultBehavior) { return this._request(t, i.RENAME, [e]) }
        migrate(e = this._defaultBehavior) { return this._request(e, i.MIGRATE, [{ appName: "Account list" }]) }
        list(e = this._iframeBehavior) { return this._request(e, i.LIST, []) }
        cashlinks(e = this._iframeBehavior) { return this._request(e, i.LIST_CASHLINKS, []) }
        _request(e, t, s) { return e.request(this._endpoint, t, s) }
    }
    return E.RequestType = i, E.RedirectRequestBehavior = f, E.PopupRequestBehavior = A, E.CashlinkState = l, E.CashlinkTheme = h, E.Currency = a, E.PaymentType = o, E.PaymentState = c, E.MSG_PREFIX = "Fyfy Signed Message:\n", E
});