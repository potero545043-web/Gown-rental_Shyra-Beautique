import { timingSafeEqual } from 'node:crypto';
import { Readable } from 'node:stream';
import { del, get, put } from '@vercel/blob';

export const config = {
    api: {
        bodyParser: false,
    },
};

function isAuthorized(req) {
    const expected = process.env.BLOB_BRIDGE_SECRET;
    const provided = req.headers['x-blob-bridge-secret'];

    if (typeof expected !== 'string' || expected.length < 32 || typeof provided !== 'string') {
        return false;
    }

    const expectedBuffer = Buffer.from(expected);
    const providedBuffer = Buffer.from(provided);

    return expectedBuffer.length === providedBuffer.length
        && timingSafeEqual(expectedBuffer, providedBuffer);
}

function accessOptions(access) {
    const prefix = access === 'public' ? 'PUBLIC' : 'PRIVATE';
    const storeId = process.env[`${prefix}_BLOB_STORE_ID`] || process.env.BLOB_STORE_ID;
    const token = process.env[`${prefix}_BLOB_READ_WRITE_TOKEN`] || process.env.BLOB_READ_WRITE_TOKEN;

    if (typeof storeId === 'string' && storeId.length > 0) {
        return { access, storeId };
    }

    if (typeof token === 'string' && token.length > 0) {
        return { access, token };
    }

    throw new Error(`${prefix}_BLOB_STORE_ID is not configured.`);
}

function allowedBlobUrl(value, access) {
    try {
        const url = new URL(value);
        const expectedHost = access === 'private' ? '.private.blob.vercel-storage.com' : '.public.blob.vercel-storage.com';

        return url.protocol === 'https:' && url.hostname.endsWith(expectedHost);
    } catch {
        return false;
    }
}

function sendError(res, status, message) {
    res.status(status).json({ error: message });
}

export default async function handler(req, res) {
    if (!isAuthorized(req)) {
        return sendError(res, 401, 'Unauthorized');
    }

    const access = req.headers['x-blob-access'];
    if (!['public', 'private'].includes(access)) {
        return sendError(res, 400, 'Invalid storage access.');
    }

    try {
        if (req.method === 'PUT') {
            const pathname = req.headers['x-blob-path'];
            const validPath = access === 'private'
                ? /^government-ids\/[A-Za-z0-9._-]+$/
                : /^(gowns|accessories)\/[A-Za-z0-9._-]+$/;
            if (typeof pathname !== 'string' || !validPath.test(pathname)) {
                return sendError(res, 400, 'Invalid file path.');
            }

            const chunks = [];
            for await (const chunk of req) {
                chunks.push(Buffer.isBuffer(chunk) ? chunk : Buffer.from(chunk));
            }

            const blob = await put(pathname, Buffer.concat(chunks), {
                ...accessOptions(access),
                addRandomSuffix: true,
                contentType: req.headers['content-type'] || 'application/octet-stream',
            });

            return res.status(201).json({ url: blob.url });
        }

        if (req.method === 'DELETE') {
            const blobUrl = req.headers['x-blob-url'];
            if (typeof blobUrl !== 'string' || !allowedBlobUrl(blobUrl, access)) {
                return sendError(res, 400, 'Invalid file URL.');
            }

            await del(blobUrl, accessOptions(access));

            return res.status(204).end();
        }

        if (req.method === 'GET' && access === 'private') {
            const blobUrl = req.headers['x-blob-url'];
            if (typeof blobUrl !== 'string' || !allowedBlobUrl(blobUrl, access)) {
                return sendError(res, 400, 'Invalid file URL.');
            }

            const blob = await get(blobUrl, accessOptions(access));
            if (!blob || blob.statusCode !== 200 || !blob.stream) {
                return sendError(res, 404, 'File not found.');
            }

            res.status(200);
            res.setHeader('Content-Type', blob.blob.contentType || 'application/octet-stream');
            res.setHeader('Cache-Control', 'private, no-store');
            res.setHeader('X-Content-Type-Options', 'nosniff');
            Readable.fromWeb(blob.stream).pipe(res);

            return;
        }

        return sendError(res, 405, 'Unsupported operation.');
    } catch (error) {
        console.error('Vercel Blob operation failed:', error);
        return sendError(res, 500, 'Blob storage operation failed.');
    }
}
