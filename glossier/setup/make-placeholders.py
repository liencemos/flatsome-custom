#!/usr/bin/env python3
"""Generate neutral product and lifestyle placeholders for the Dewier demo."""

import json
import random
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
OUT = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / "child-theme" / "dewier-child" / "assets" / "images"
OUT.mkdir(parents=True, exist_ok=True)
CATALOG = json.loads((ROOT / "setup" / "catalog.json").read_text())
S = 2  # supersampling factor
W, H = 1000, 1200
CARD_BG = "#f7f7f7"


def rgb(value):
    value = value.lstrip("#")
    return tuple(int(value[i : i + 2], 16) for i in (0, 2, 4))


def mix(a, b, t):
    a, b = rgb(a) if isinstance(a, str) else a, rgb(b) if isinstance(b, str) else b
    return tuple(round(a[i] + (b[i] - a[i]) * t) for i in range(3))


def canvas(bg, w=W, h=H):
    return Image.new("RGB", (w * S, h * S), bg)


def save(img, name, w=W, h=H, quality=80):
    img.resize((w, h), Image.LANCZOS).save(OUT / name, "JPEG", quality=quality, optimize=True, progressive=True)


class Pen:
    """Draws shaded solids in 1000x1200 coordinates."""

    def __init__(self, img):
        self.img = img
        self.d = ImageDraw.Draw(img)

    def box(self, x0, y0, x1, y1, color, r=0):
        self.d.rounded_rectangle([x0 * S, y0 * S, x1 * S, y1 * S], radius=r * S, fill=color)

    def solid(self, x0, y0, x1, y1, color, r=0):
        """Rounded body with a soft left highlight and right shade."""
        color = rgb(color) if isinstance(color, str) else color
        w, h = int((x1 - x0) * S), int((y1 - y0) * S)
        layer = Image.new("RGB", (w, h), color)
        px = layer.load()
        for x in range(w):
            t = x / max(1, w - 1)
            shade = 0.10 * (1 - t) ** 3 if t < 0.5 else -0.13 * ((t - 0.5) * 2) ** 2
            col = mix(color, (255, 255, 255), shade) if shade > 0 else mix(color, (0, 0, 0), -shade)
            for y in range(h):
                px[x, y] = col
        mask = Image.new("L", (w, h), 0)
        ImageDraw.Draw(mask).rounded_rectangle([0, 0, w - 1, h - 1], radius=int(r * S), fill=255)
        self.img.paste(layer, (int(x0 * S), int(y0 * S)), mask)

    def shadow(self, cx, y, half):
        layer = Image.new("RGBA", self.img.size, (0, 0, 0, 0))
        ImageDraw.Draw(layer).ellipse(
            [(cx - half) * S, (y - 14) * S, (cx + half * 1.5) * S, (y + 14) * S], fill=(0, 0, 0, 46)
        )
        layer = layer.filter(ImageFilter.GaussianBlur(14 * S))
        self.img.paste(layer, (0, 0), layer)

    def label(self, x0, y0, x1, y1, color="#1a1a1a"):
        self.box(x0, y0, x1, y1, color, r=min(x1 - x0, y1 - y0) / 2)


def draw_shape(pen, shape, color, accent, cx=500, base=960, k=1.3):
    def X(v):
        return cx + v * k

    def Y(v):
        return base - v * k

    ink = mix(color, "#000000", 0.55)
    if shape == "bottle":
        pen.shadow(cx, base, 130 * k)
        pen.solid(X(-120), Y(430), X(120), Y(0), color, r=46 * k)
        pen.solid(X(-58), Y(540), X(58), Y(424), accent, r=14 * k)
        pen.label(X(-34), Y(120), X(34), Y(108), ink)
    elif shape == "tube":
        pen.shadow(cx, base, 80 * k)
        pen.solid(X(-70), Y(520), X(70), Y(110), color, r=18 * k)
        pen.box(X(-70), Y(520), X(70), Y(500), mix(color, "#000000", 0.08), r=4 * k)
        pen.solid(X(-52), Y(120), X(52), Y(0), accent, r=10 * k)
        pen.label(X(-8), Y(400), X(8), Y(250), ink)
    elif shape == "jar":
        pen.shadow(cx, base, 170 * k)
        pen.solid(X(-160), Y(170), X(160), Y(0), color, r=34 * k)
        pen.solid(X(-170), Y(250), X(170), Y(160), accent, r=20 * k)
    elif shape == "compact":
        pen.shadow(cx, base, 170 * k)
        pen.solid(X(-165), Y(70), X(165), Y(0), accent, r=26 * k)
        pen.solid(X(-165), Y(320), X(165), Y(76), color, r=30 * k)
        pen.d.ellipse([X(-60) * S, Y(250) * S, X(60) * S, Y(130) * S], fill=mix(color, "#ffffff", 0.35))
    elif shape in ("stick", "lipstick"):
        pen.shadow(cx, base, 60 * k)
        pen.solid(X(-50), Y(300), X(50), Y(0), color, r=12 * k)
        pen.solid(X(-42), Y(380), X(42), Y(296), mix(color, "#ffffff", 0.5), r=6 * k)
        pen.solid(X(-34), Y(470), X(34), Y(376), accent, r=22 * k)
        pen.label(X(-7), Y(220), X(7), Y(80), ink)
    elif shape == "mascara":
        pen.shadow(cx, base, 100 * k)
        pen.solid(X(-110), Y(430), X(-20), Y(0), color, r=16 * k)
        pen.label(X(-72), Y(330), X(-58), Y(120), ink)
        pen.solid(X(40), Y(190), X(120), Y(0), color, r=14 * k)
        pen.solid(X(72), Y(560), X(88), Y(186), accent, r=6 * k)
        pen.solid(X(62), Y(560), X(98), Y(440), accent, r=16 * k)
    elif shape == "pencil":
        pen.shadow(cx, base, 60 * k)
        pen.solid(X(-16), Y(640), X(16), Y(60), accent, r=6 * k)
        pen.d.polygon([(X(-16) * S, Y(60) * S), (X(16) * S, Y(60) * S), (X(0) * S, Y(0) * S)], fill=mix(accent, "#000000", 0.25))
        pen.solid(X(60), Y(190), X(100), Y(0), accent, r=8 * k)
    elif shape == "dropper":
        pen.shadow(cx, base, 110 * k)
        pen.solid(X(-105), Y(300), X(105), Y(0), accent, r=30 * k)
        pen.solid(X(-46), Y(400), X(46), Y(292), color, r=10 * k)
        pen.solid(X(-30), Y(470), X(30), Y(396), color, r=26 * k)
        pen.d.ellipse([X(-52) * S, Y(210) * S, X(52) * S, Y(106) * S], fill=mix(accent, "#ffffff", 0.9))
    elif shape == "pump":
        pen.shadow(cx, base, 120 * k)
        pen.solid(X(-110), Y(420), X(110), Y(0), color, r=30 * k)
        pen.solid(X(-44), Y(480), X(44), Y(414), accent if accent != color else mix(color, "#000000", 0.06), r=8 * k)
        pen.solid(X(-14), Y(560), X(14), Y(476), mix(color, "#000000", 0.08), r=4 * k)
        pen.solid(X(-90), Y(590), X(34), Y(552), mix(color, "#000000", 0.08), r=12 * k)
        pen.label(X(-40), Y(240), X(40), Y(228), ink)
    elif shape == "bag":
        pen.shadow(cx, base, 220 * k)
        pen.solid(X(-220), Y(360), X(220), Y(0), color, r=40 * k)
        pen.box(X(-220), Y(330), X(220), Y(318), accent, r=4 * k)
        pen.solid(X(150), Y(350), X(200), Y(300), accent, r=8 * k)
    elif shape in ("duo", "trio"):
        offsets = (-95, 95) if shape == "duo" else (-170, 0, 170)
        for i, dx in enumerate(offsets):
            tint = mix(color, accent, 0.0 if i == 0 else 0.25 * i)
            draw_shape(pen, "tube", "#%02x%02x%02x" % tint, accent, cx=cx + dx * k, base=base, k=k * 0.8)
    elif shape == "box":
        pen.shadow(cx, base, 250 * k)
        pen.solid(X(-250), Y(600), X(250), Y(0), color, r=10 * k)
        pen.box(X(-196), Y(520), X(196), Y(80), mix(color, "#ffffff", 0.55), r=6 * k)
        pen.label(X(-90), Y(576), X(90), Y(546), ink)
        draw_shape(pen, "bottle", "#%02x%02x%02x" % mix(color, "#ffffff", 0.3), accent, cx=cx - 70 * k, base=Y(110), k=k * 0.55)
        draw_shape(pen, "stick", "#%02x%02x%02x" % mix(accent, "#ffffff", 0.4), accent, cx=cx + 90 * k, base=Y(110), k=k * 0.62)


def product_image(product):
    img = canvas(CARD_BG)
    draw_shape(Pen(img), product["shape"], product["color"], product["accent"])
    save(img, f"product-{product['slug']}.jpg")


def texture_image(name, colors, seed):
    """Soft blurred colour field used for swatch/texture and lifestyle shots."""
    rnd = random.Random(seed)
    img = Image.new("RGB", (W, H), colors[0])
    draw = ImageDraw.Draw(img)
    for i in range(9):
        col = colors[1 + i % (len(colors) - 1)]
        x, y, r = rnd.randint(-100, W), rnd.randint(-100, H), rnd.randint(220, 520)
        draw.ellipse([x - r, y - r, x + r, y + r], fill=col)
    img = img.filter(ImageFilter.GaussianBlur(120))
    img.save(OUT / name, "JPEG", quality=72, optimize=True, progressive=True)


def lifestyle(name, bg, items, seed, w=W, h=H):
    """Still life: blurred backdrop with a few oversized products."""
    rnd = random.Random(seed)
    img = Image.new("RGB", (w * S, h * S), bg)
    draw = ImageDraw.Draw(img)
    for _ in range(6):
        col = mix(bg, rnd.choice(["#ffffff", "#f3c9cf", "#c8102e", "#f4e7c8"]), rnd.uniform(0.25, 0.6))
        x, y, r = rnd.randint(0, w * S), rnd.randint(0, h * S), rnd.randint(200, 500) * S
        draw.ellipse([x - r, y - r, x + r, y + r], fill=col)
    img = img.filter(ImageFilter.GaussianBlur(90 * S))
    pen = Pen(img)
    for shape, color, accent, cx, base, k in items:
        draw_shape(pen, shape, color, accent, cx=cx * w, base=base * h, k=k * w / W)
    save(img, name, w, h, quality=76)


def main():
    for product in CATALOG["products"]:
        product_image(product)
        tones = [product["accent"], product["color"]] + list(product.get("shades", {}).values())[:3]
        base = "#%02x%02x%02x" % mix(product["color"], "#ffffff", 0.5)
        texture_image(f"texture-{product['slug']}.jpg", [base] + tones, product["slug"])

    tiles = {
        "fragrance": ("#f6dfe2", [("bottle", "#f3c9cf", "#c8102e", 0.5, 0.98, 1.9)]),
        "makeup": ("#ececec", [("mascara", "#ffffff", "#1a1a1a", 0.3, 1.0, 1.6), ("mascara", "#f6d5dc", "#3e2e20", 0.72, 1.0, 1.6)]),
        "lip": ("#f3c1c1", [("lipstick", "#f4d3da", "#b0122f", 0.5, 1.02, 2.4)]),
        "skincare": ("#eef1f4", [("pump", "#ffffff", "#ffffff", 0.5, 1.02, 1.9)]),
        "sets": ("#efe6da", [("bottle", "#f3c9cf", "#c8102e", 0.68, 0.96, 1.3), ("stick", "#f6d5dc", "#c8102e", 0.3, 0.96, 1.5)]),
        "body": ("#f4e7c8", [("pump", "#f4e7c8", "#ffffff", 0.5, 1.0, 1.8)]),
        "balms": ("#d9ecf7", [("tube", "#bfe0f2", "#f4e3a1", 0.5, 1.0, 1.9)]),
    }
    for i, (slug, (bg, items)) in enumerate(tiles.items()):
        lifestyle(f"tile-{slug}.jpg", bg, items, 100 + i, 800, 1000)

    social = [
        ("#f0dfe2", [("pump", "#f7dfe3", "#c8102e", 0.4, 0.86, 1.3), ("jar", "#ffffff", "#f3c9cf", 0.68, 0.9, 0.9)]),
        ("#c8102e", [("compact", "#f6d5dc", "#ffffff", 0.5, 0.74, 1.6)]),
        ("#dfe3f6", [("tube", "#f2f2f2", "#e8758a", 0.42, 0.88, 1.5), ("pencil", "#ffffff", "#b8475a", 0.66, 0.88, 1.2)]),
        ("#f6d5dc", [("box", "#f1e4c5", "#c8102e", 0.5, 0.9, 1.25)]),
        ("#efe6da", [("bottle", "#f4e7c8", "#f3ead7", 0.5, 0.9, 1.5)]),
        ("#e7eee4", [("trio", "#bfe0f2", "#f4e3a1", 0.5, 0.86, 1.5)]),
    ]
    for i, (bg, items) in enumerate(social, 1):
        lifestyle(f"social-{i}.jpg", bg, items, 200 + i, 900, 1125)

    lifestyle("story.jpg", "#8a6f5c", [("bottle", "#f3c9cf", "#c8102e", 0.32, 0.9, 1.2), ("jar", "#d92b3a", "#c8102e", 0.62, 0.9, 1.0), ("stick", "#f6d5dc", "#b8475a", 0.84, 0.9, 1.0)], 301, 1500, 1000)
    lifestyle("gift.jpg", "#ffffff", [("jar", "#d92b3a", "#e23b48", 0.5, 0.82, 1.9)], 302, 600, 400)
    lifestyle("promo-tile.jpg", "#e8e2dc", [("jar", "#d92b3a", "#e23b48", 0.5, 0.7, 2.2)], 303, 800, 1340)
    lifestyle("review.jpg", "#f1f1f1", [("tube", "#f2f2f2", "#e8758a", 0.5, 1.1, 3.4)], 304, 1500, 1000)
    lifestyle("how-to.jpg", "#d9c3b4", [("tube", "#bfe0f2", "#f4e3a1", 0.6, 1.0, 1.7)], 305, 1200, 750)
    print(f"Wrote {len(list(OUT.glob('*.jpg')))} images to {OUT}")


if __name__ == "__main__":
    main()
