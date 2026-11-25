const CACHE = 'rota-facil-v1';
const ESTATICOS = ['/css/style.css', '/css/desktop.css', '/css/passagens.css', '/css/laravel.css', '/assets/logo-rota-facil.png', '/assets/favicon-rota-facil.png'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ESTATICOS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((chaves) => Promise.all(chaves.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  const url = new URL(req.url);
  if (req.method !== 'GET' || url.origin !== location.origin) return;

  const estatico = url.pathname.startsWith('/css/') || url.pathname.startsWith('/assets/');
  if (!estatico) return; // páginas e formulários sempre vão ao servidor

  e.respondWith(
    fetch(req).then((res) => {
      const copia = res.clone();
      caches.open(CACHE).then((c) => c.put(req, copia));
      return res;
    }).catch(() => caches.match(req))
  );
});
