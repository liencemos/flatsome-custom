#!/usr/bin/env python3
"""Build the demo image set in comfa/demo-images from setup/art-src.

Product shots are white garment cut-outs that get tinted per colour, so adding a
colour to catalog.json only needs a re-run of this script (requires Pillow + numpy).
"""
import json
from pathlib import Path

import numpy as np
from PIL import Image, ImageFilter

ROOT = Path(__file__).resolve().parent
SRC = ROOT / "art-src"
OUT = ROOT.parent / "demo-images"
CARD_BG = (243, 243, 241)
CARD = (1200, 1600)
TILE_BGS = ["#f0d9a6", "#c9d6b8", "#f2c6b4", "#bcd0ea", "#e9cfd6", "#d8d2c2"]


def hex_rgb(value):
    value = value.lstrip("#")
    return tuple(int(value[i:i + 2], 16) for i in (0, 2, 4))


def tint(cutout, colour, has_print):
    rgba = np.asarray(cutout).astype(np.float32) / 255
    rgb, alpha = rgba[..., :3], rgba[..., 3]
    lum = rgb @ np.array([0.299, 0.587, 0.114], dtype=np.float32)
    sat = rgb.max(axis=2) - rgb.min(axis=2)
    ref = np.percentile(lum[alpha > 0.9], 90)
    shade = np.clip(lum / ref, 0, 1.06) ** 1.7
    target = np.array(colour, dtype=np.float32) / 255
    dark = target.mean() < 0.42
    if dark:
        # Lift folds a little so dark garments keep visible structure.
        shade = 0.55 + 0.75 * (shade - 0.55) + 0.12 * np.clip(1 - shade, 0, 1)
    out = shade[..., None] * target[None, None, :]
    if has_print:
        coloured = np.clip((sat - 0.16) / 0.2, 0, 1)[..., None]
        ink = (np.clip((0.5 - lum) / 0.25, 0, 1) * (1 - coloured[..., 0]))[..., None]
        ink_colour = np.array([0.95, 0.93, 0.88] if dark else [0.1, 0.1, 0.11], dtype=np.float32)
        out = out * (1 - ink) + ink_colour * ink
        out = out * (1 - coloured) + rgb * coloured
    out = np.dstack([np.clip(out, 0, 1), alpha])
    return Image.fromarray((out * 255).astype(np.uint8), "RGBA")


def place(garment, size, bg, fill=0.8, shadow=True):
    canvas = Image.new("RGB", size, bg)
    scale = min(size[0] * fill / garment.width, size[1] * fill / garment.height)
    item = garment.resize((round(garment.width * scale), round(garment.height * scale)), Image.LANCZOS)
    x, y = (size[0] - item.width) // 2, (size[1] - item.height) // 2
    if shadow:
        mask = Image.new("L", size, 0)
        mask.paste(item.getchannel("A"), (x, y + round(size[1] * 0.012)))
        mask = mask.filter(ImageFilter.GaussianBlur(size[0] * 0.018)).point(lambda v: int(v * 0.22))
        canvas.paste((40, 40, 46), (0, 0), mask)
    canvas.paste(item, (x, y), item)
    return canvas


def cover(image, size):
    scale = max(size[0] / image.width, size[1] / image.height)
    image = image.resize((round(image.width * scale) + 1, round(image.height * scale) + 1), Image.LANCZOS)
    left, top = (image.width - size[0]) // 2, (image.height - size[1]) // 2
    return image.crop((left, top, left + size[0], top + size[1]))


def save(image, name, quality=82):
    image.save(OUT / name, quality=quality, method=6)


def main():
    catalog = json.loads((ROOT / "catalog.json").read_text())
    OUT.mkdir(exist_ok=True)
    for old in OUT.glob("*.webp"):
        old.unlink()
    bases = {p.stem: Image.open(p).convert("RGBA") for p in (SRC / "base").glob("*.webp")}
    for product in catalog["products"]:
        cutout = bases[product["base"]]
        fill = 0.62 if product["cat"] == "caps" else (0.66 if product["cat"] == "socks" else 0.84)
        for index, colour in enumerate(product["colors"]):
            garment = tint(cutout, hex_rgb(catalog["colors"][colour][1]), product["print"])
            save(place(garment, CARD, CARD_BG, fill), f"{product['slug']}-{colour}.webp")
            if index == 0:
                # Close-up used as a second gallery image.
                zoom = place(garment, (CARD[0] * 2, CARD[1] * 2), CARD_BG, fill, shadow=False)
                box = (CARD[0] // 2, round(CARD[1] * 0.42), CARD[0] // 2 + CARD[0], round(CARD[1] * 0.42) + CARD[1])
                save(zoom.crop(box), f"{product['slug']}-detail.webp")

    # Sub-category tiles: product cut-out on a soft colour field.
    tiles = [("crew-neck", "tee-crew", "navy"), ("v-neck", "tee-vneck", "pink"), ("long-sleeve", "tee-long", "burgundy"),
             ("dad-caps", "cap-dad", "sage"), ("snapbacks", "cap-snap", "black"), ("beanies", "cap-beanie", "clay"),
             ("graphic-tees", "tee-sun", "white"), ("camp-caps", "cap-cord", "mustard")]
    for index, (slug, base, colour) in enumerate(tiles):
        garment = tint(bases[base], hex_rgb(catalog["colors"][colour][1]), base in ("tee-sun",))
        save(place(garment, (720, 720), hex_rgb(TILE_BGS[index % len(TILE_BGS)]), 0.72), f"sub-{slug}.webp")

    for path in (SRC / "life").glob("*.jpg"):
        image = Image.open(path).convert("RGB")
        if path.stem == "hero":
            save(cover(image, (1400, 1040)), "hero.webp", 80)
        elif path.stem == "mission":
            save(cover(image, (1500, 1000)), "mission.webp", 80)
        elif path.stem == "illus-give":
            rgb = np.asarray(image).astype(np.float32)
            bg = rgb[8, 8]
            dist = np.abs(rgb - bg).max(axis=2)
            alpha = np.clip((dist - 18) / 40, 0, 1)
            out = np.dstack([rgb, alpha * 255]).astype(np.uint8)
            Image.fromarray(out, "RGBA").resize((900, 900), Image.LANCZOS).save(OUT / "illus-give.webp", quality=90, method=6)
        else:
            save(cover(image, (1000, 1000)), f"{path.stem}.webp", 80)
            if path.stem.startswith("tile-"):
                save(cover(image, (800, 280)), f"chip-{path.stem[5:]}.webp", 78)
    print(f"{len(list(OUT.glob('*.webp')))} images written to {OUT}")


if __name__ == "__main__":
    main()
