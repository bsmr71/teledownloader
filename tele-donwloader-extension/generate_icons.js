// Generate PNG icons without external dependencies
// Uses minimal valid PNG generation

const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

function generatePNG(size) {
  const width = size;
  const height = size;
  
  // Create pixel data (RGBA)
  const pixelData = Buffer.alloc(height * (1 + width * 4)); // filter byte + pixels per row
  
  const cx = width / 2;
  const cy = height / 2;
  const radius = size * 0.2;
  
  for (let y = 0; y < height; y++) {
    const rowOffset = y * (1 + width * 4);
    pixelData[rowOffset] = 0; // filter: none
    
    for (let x = 0; x < width; x++) {
      const offset = rowOffset + 1 + x * 4;
      
      // Check if inside rounded rect
      if (isInsideRoundedRect(x, y, 0, 0, width, height, radius)) {
        // Gradient from #2AABEE to #1565a8
        const t = (x + y) / (width + height);
        const r = Math.round(lerp(0x2A, 0x15, t));
        const g = Math.round(lerp(0xAB, 0x65, t));
        const b = Math.round(lerp(0xEE, 0xa8, t));
        
        // Check if this pixel is part of the download icon
        if (isDownloadIcon(x, y, cx, cy, size)) {
          // White pixel
          pixelData[offset] = 255;
          pixelData[offset + 1] = 255;
          pixelData[offset + 2] = 255;
          pixelData[offset + 3] = 255;
        } else {
          pixelData[offset] = r;
          pixelData[offset + 1] = g;
          pixelData[offset + 2] = b;
          pixelData[offset + 3] = 255;
        }
      } else {
        // Transparent
        pixelData[offset] = 0;
        pixelData[offset + 1] = 0;
        pixelData[offset + 2] = 0;
        pixelData[offset + 3] = 0;
      }
    }
  }
  
  return createPNGBuffer(width, height, pixelData);
}

function isInsideRoundedRect(x, y, rx, ry, w, h, r) {
  // Check corners
  if (x < rx + r && y < ry + r) {
    return distance(x, y, rx + r, ry + r) <= r;
  }
  if (x > rx + w - r && y < ry + r) {
    return distance(x, y, rx + w - r, ry + r) <= r;
  }
  if (x < rx + r && y > ry + h - r) {
    return distance(x, y, rx + r, ry + h - r) <= r;
  }
  if (x > rx + w - r && y > ry + h - r) {
    return distance(x, y, rx + w - r, ry + h - r) <= r;
  }
  return x >= rx && x < rx + w && y >= ry && y < ry + h;
}

function distance(x1, y1, x2, y2) {
  return Math.sqrt((x1 - x2) ** 2 + (y1 - y2) ** 2);
}

function lerp(a, b, t) {
  return a + (b - a) * t;
}

function isDownloadIcon(x, y, cx, cy, size) {
  const s = size * 0.25;
  const lw = Math.max(size * 0.06, 1);
  
  // Vertical line (shaft)
  const shaftTop = cy - s;
  const shaftBottom = cy + s * 0.6;
  if (Math.abs(x - cx) <= lw && y >= shaftTop && y <= shaftBottom) {
    return true;
  }
  
  // Arrow head
  const arrowTipY = cy + s * 0.7;
  const arrowTopY = cy + s * 0.05;
  if (y >= arrowTopY && y <= arrowTipY) {
    const progress = (y - arrowTopY) / (arrowTipY - arrowTopY);
    const halfWidth = s * 0.6 * (1 - progress);
    // Left wing
    const expectedX = cx - s * 0.6 * (1 - progress);
    if (Math.abs(x - expectedX) <= lw) return true;
    // Right wing
    const expectedXr = cx + s * 0.6 * (1 - progress);
    if (Math.abs(x - expectedXr) <= lw) return true;
  }
  
  // Bottom tray line
  const trayY = cy + s;
  const trayHalfW = s * 0.8;
  if (Math.abs(y - trayY) <= lw && x >= cx - trayHalfW && x <= cx + trayHalfW) {
    return true;
  }
  
  return false;
}

function createPNGBuffer(width, height, pixelData) {
  // Compress pixel data
  const compressed = zlib.deflateSync(pixelData);
  
  // PNG signature
  const signature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);
  
  // IHDR chunk
  const ihdr = createChunk('IHDR', (() => {
    const buf = Buffer.alloc(13);
    buf.writeUInt32BE(width, 0);
    buf.writeUInt32BE(height, 4);
    buf[8] = 8;  // bit depth
    buf[9] = 6;  // color type: RGBA
    buf[10] = 0; // compression
    buf[11] = 0; // filter
    buf[12] = 0; // interlace
    return buf;
  })());
  
  // IDAT chunk
  const idat = createChunk('IDAT', compressed);
  
  // IEND chunk
  const iend = createChunk('IEND', Buffer.alloc(0));
  
  return Buffer.concat([signature, ihdr, idat, iend]);
}

function createChunk(type, data) {
  const length = Buffer.alloc(4);
  length.writeUInt32BE(data.length, 0);
  
  const typeBuffer = Buffer.from(type, 'ascii');
  const crcData = Buffer.concat([typeBuffer, data]);
  const crc = crc32(crcData);
  const crcBuffer = Buffer.alloc(4);
  crcBuffer.writeUInt32BE(crc, 0);
  
  return Buffer.concat([length, typeBuffer, data, crcBuffer]);
}

function crc32(buf) {
  let crc = 0xFFFFFFFF;
  for (let i = 0; i < buf.length; i++) {
    crc ^= buf[i];
    for (let j = 0; j < 8; j++) {
      if (crc & 1) {
        crc = (crc >>> 1) ^ 0xEDB88320;
      } else {
        crc = crc >>> 1;
      }
    }
  }
  return (crc ^ 0xFFFFFFFF) >>> 0;
}

// Generate all icon sizes
const iconsDir = path.join(__dirname, 'icons');
if (!fs.existsSync(iconsDir)) {
  fs.mkdirSync(iconsDir, { recursive: true });
}

[16, 32, 48, 128].forEach(size => {
  const png = generatePNG(size);
  const filePath = path.join(iconsDir, `icon${size}.png`);
  fs.writeFileSync(filePath, png);
  console.log(`✅ Created icon${size}.png (${png.length} bytes)`);
});

console.log('\n🎉 All icons generated successfully!');
