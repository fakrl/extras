require('dotenv').config();

const express = require('express');
const qrcode = require('qrcode-terminal');
const { Client, LocalAuth } = require('whatsapp-web.js');

const PORT = process.env.PORT || 3001;
const TOKEN = process.env.WHATSAPP_SERVICE_TOKEN || '';

const app = express();
app.use(express.json());

const client = new Client({
  authStrategy: new LocalAuth(), // session persisten, tidak perlu scan QR ulang tiap restart
});

let siap = false;

client.on('qr', (qr) => {
  console.log('Scan QR code ini dengan WhatsApp (Perangkat Tertaut):');
  qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
  siap = true;
  console.log('WhatsApp client siap.');
});

client.on('disconnected', (reason) => {
  siap = false;
  console.log('WhatsApp client terputus:', reason);
});

client.initialize();

function cekToken(req, res, next) {
  const auth = req.get('Authorization') || '';
  if (!TOKEN || auth !== `Bearer ${TOKEN}`) {
    return res.status(401).json({ sukses: false, pesan: 'Token tidak valid.' });
  }
  next();
}

app.post('/send', cekToken, async (req, res) => {
  const { nomor, pesan } = req.body || {};

  if (!nomor || !pesan) {
    return res.status(422).json({ sukses: false, pesan: 'Field nomor dan pesan wajib diisi.' });
  }

  if (!siap) {
    return res.status(503).json({ sukses: false, pesan: 'WhatsApp client belum siap (belum scan QR / masih connect).' });
  }

  try {
    await client.sendMessage(`${nomor}@c.us`, pesan);
    return res.json({ sukses: true });
  } catch (err) {
    console.error('Gagal kirim WA:', err.message);
    return res.status(500).json({ sukses: false, pesan: 'Gagal kirim pesan.' });
  }
});

app.get('/health', (req, res) => {
  res.json({ siap });
});

app.listen(PORT, '127.0.0.1', () => {
  console.log(`WhatsApp service jalan di port ${PORT}`);
});
