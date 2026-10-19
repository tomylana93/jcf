// Runs the production Inertia renderer over HTTP for SSR integration tests.
import { createServer } from 'node:http';

const { default: render } = await import('../../bootstrap/ssr/app.js');
const server = createServer(async (request, response) => {
    if (request.url === '/health') {
        response.end('OK');
        return;
    }
    const chunks = [];
    for await (const chunk of request) chunks.push(chunk);
    try {
        const page = JSON.parse(Buffer.concat(chunks).toString());
        const result = await render(page);
        response.setHeader('Content-Type', 'application/json');
        response.end(JSON.stringify(result));
    } catch (error) {
        response.writeHead(500, { 'Content-Type': 'application/json' });
        response.end(JSON.stringify({ error: String(error) }));
    }
});
server.listen(13719, '127.0.0.1', () => console.log('PUBLIC_SEO_SSR_READY'));
