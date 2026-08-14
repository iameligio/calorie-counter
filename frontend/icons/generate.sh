#!/usr/bin/env bash
#
# Regenerates the PWA raster icons in public/ from their SVG sources.
# Run after editing either source: `npm run icons`
#
# Two sources, because the outputs have different corner requirements:
#   public/icon.svg           — rounded rect. Ships as the favicon, and is the
#                               manifest's `purpose: any` icon.
#   icons/icon-fullbleed.svg  — square, edge to edge. Android masks the
#                               maskable icon itself, and iOS composites any
#                               transparency onto black.
#
# Rasterised with macOS `sips`, which reads SVG directly and needs no
# sharp/node-canvas toolchain. On Linux use librsvg instead:
#   rsvg-convert -w SIZE -h SIZE SRC -o DEST
set -euo pipefail

cd "$(dirname "$0")/.."

render() { # render <src> <size> <dest>
  sips -s format png -Z "$2" "$1" --out "$3" >/dev/null
  echo "  $3 (${2}x${2})"
}

echo "Regenerating PWA icons:"
render public/icon.svg          192 public/pwa-192.png
render public/icon.svg          512 public/pwa-512.png
render icons/icon-fullbleed.svg 512 public/maskable-512.png
render icons/icon-fullbleed.svg 180 public/apple-touch-icon.png

# Manifest shortcut icons (long-press menu on Android). 96px is the size
# Android asks for; the launcher masks them to a circle, same as maskable.
for s in dashboard history settings; do
  render "icons/shortcut-$s.svg" 96 "public/shortcut-$s.png"
done
