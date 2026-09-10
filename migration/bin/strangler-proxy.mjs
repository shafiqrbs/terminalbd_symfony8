/**
 * The strangler proxy, locally.
 *
 * The port is meant to be cut over module by module behind a proxy: a module's
 * paths go to the React application, everything else stays with the Twig one,
 * and rollback is one rule. In production that is nginx. This is the same thing
 * on one port, so the whole arrangement can actually be run and tested here.
 *
 * It is also the only way to prove the session bridge end to end. The API, the
 * Symfony 8 tree and the Symfony 2 tree run on three ports, and a browser will
 * not send a cookie set by one origin to another - so without a proxy the bridge
 * cannot even be attempted in a browser, which is where it has to work.
 *
 * Rules, in order:
 *   /api/...            -> the API            (8810)
 *   Vite's own paths    -> the React app      (3000)
 *   a ported screen     -> the React app      (3000)
 *   LEGACY_PATHS        -> the Symfony 2 app  (8801)
 *   everything else     -> the Symfony 8 app  (8802)
 *
 * The ported screens are read from the React app's generated route table, so a
 * module that lands is routed here by having landed, not by editing this file.
 *
 * usage: node migration/bin/strangler-proxy.mjs [--port=8080]
 */

import http from "node:http";
import { readFileSync } from "node:fs";

const PORT = Number(process.argv.find((a) => a.startsWith("--port="))?.slice(7) ?? 8080);
const API = { host: "127.0.0.1", port: 8810 };
const REACT = { host: "127.0.0.1", port: 3000 };
const LEGACY = { host: "127.0.0.1", port: 8801 };
const NEXT = { host: "127.0.0.1", port: 8802 };

const navPath = "/home/sreejon/projects/personal/terminalbd_react/src/routes/nav.js";
const nav = readFileSync(navPath, "utf8");

// The generated table is JS; the two things needed from it are plain JSON.
const NAV = JSON.parse(nav.match(/export const NAV = (\{[\s\S]*?\n\});/)[1]);
const IMPLEMENTED = new Set(JSON.parse(nav.match(/export const IMPLEMENTED = new Set\(([\s\S]*?)\);/)[1]));
const ported = new Set(
	Object.values(NAV).flat().filter((i) => IMPLEMENTED.has(i.route)).map((i) => i.path)
);

// Paths deliberately left with the Symfony 2 application, to exercise the
// bridge: a user who logged in through React has to arrive here authenticated.
const legacyPaths = new Set((process.env.LEGACY_PATHS ?? "").split(",").filter(Boolean));

const VITE = ["/@vite", "/@react-refresh", "/@fs", "/src/", "/node_modules/", "/vite.svg"];

function target(url) {
	if (url.startsWith("/api/")) return { upstream: API, name: "api" };
	if (VITE.some((p) => url.startsWith(p))) return { upstream: REACT, name: "react" };

	const path = url.split("?")[0];

	if (legacyPaths.has(path)) return { upstream: LEGACY, name: "legacy" };
	if (ported.has(path)) return { upstream: REACT, name: "react" };

	return { upstream: NEXT, name: "next" };
}

const server = http.createServer((req, res) => {
	const { upstream, name } = target(req.url);
	const proxied = http.request(
		{
			host: upstream.host,
			port: upstream.port,
			method: req.method,
			path: req.url,
			headers: { ...req.headers, host: `${upstream.host}:${upstream.port}` },
		},
		(upstreamRes) => {
			// A redirect from an upstream names its own origin; rewrite it to
			// this one or the browser leaves the proxy and loses the cookie.
			const headers = { ...upstreamRes.headers };

			if (typeof headers.location === "string") {
				headers.location = headers.location.replace(
					/^https?:\/\/127\.0\.0\.1:(8810|8802|8801|3000)/,
					`http://127.0.0.1:${PORT}`
				);
			}

			res.writeHead(upstreamRes.statusCode ?? 502, headers);
			upstreamRes.pipe(res);
		}
	);

	proxied.on("error", (error) => {
		res.writeHead(502, { "content-type": "text/plain" });
		res.end(`${name} upstream unreachable: ${error.message}\n`);
	});

	req.pipe(proxied);
});

server.listen(PORT, "127.0.0.1", () => {
	console.log(`strangler proxy on http://127.0.0.1:${PORT}`);
	console.log(`  ${ported.size} ported screens -> react`);
	console.log(`  ${legacyPaths.size} path(s) -> the Symfony 2 application`);
	console.log("  everything else -> next/");
});
