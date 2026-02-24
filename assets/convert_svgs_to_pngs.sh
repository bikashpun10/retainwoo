#!/usr/bin/env bash
set -euo pipefail

# Convert SVG placeholders to PNGs using ImageMagick `convert`.
# Usage: run from the `assets` directory: ./convert_svgs_to_pngs.sh

command -v convert >/dev/null 2>&1 || { echo "ImageMagick 'convert' not found. Install ImageMagick or use rsvg-convert."; exit 1; }

PWD_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PWD_DIR"

echo "Converting banners..."
convert banner-772x250.svg -background none -resize 772x250 banner-772x250.png
convert banner-1544x500.svg -background none -resize 1544x500 banner-1544x500.png

echo "Converting icons..."
convert icon-256x256.svg -background none -resize 256x256 icon-256x256.png
convert icon-128x128.svg -background none -resize 128x128 icon-128x128.png

mkdir -p screenshots

echo "Converting screenshots..."
convert screenshots/screenshot-1.svg -background none -resize 1200x900 screenshots/screenshot-1.png
convert screenshots/screenshot-2.svg -background none -resize 1200x900 screenshots/screenshot-2.png

echo "Done. Generated PNG placeholders in: $PWD_DIR"
