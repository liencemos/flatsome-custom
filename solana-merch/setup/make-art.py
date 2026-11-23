from io import BytesIO
from pathlib import Path
from random import Random

import cairosvg
from PIL import Image, ImageChops, ImageFilter

OUT = Path(__file__).resolve().parents[1] / "child-theme/solana-merch-child/assets/art"
TEES = ("carrier-wave", "night-grid", "orbital-index", "relay-station", "static-field", "after-hours")
CAPS = ("receiver-cap", "sideband-cap", "orbit-cap", "uplink-cap", "field-cap", "nightshift-cap")
TEE = "M250 82Q222 95 166 104L126 121 37 193Q54 247 91 291L174 254Q171 354 164 506Q296 519 437 508L430 254 516 293Q550 252 565 194L477 122 438 104Q382 94 351 82Q307 105 250 82Z"
CROWN = "M82 373Q65 343 87 269Q115 169 211 133Q300 97 381 154Q441 195 462 276L462 336Q415 395 279 411Q138 429 82 373Z"
BILL = "M248 410Q336 345 462 336Q524 353 563 403Q584 428 554 445Q497 476 409 467Q327 460 248 410Z"
PRINTS = (
    '<path d="M0 0h68v22H25v46H0zM34 33h34v35H34z"/>',
    '<path d="M0 0h34v34H0zM34 34h34v34H34zM45 0h23v23H45zM0 45h23v23H0z"/>',
    '<path fill-rule="evenodd" d="M34 0a34 34 0 1 0 0 68 34 34 0 0 0 0-68zm0 13a21 21 0 1 1 0 42 21 21 0 0 1 0-42z"/><path d="M0 30h68v8H0z"/>',
    '<path d="M34 0 68 68H54L34 28 14 68H0zM27 46h14l-7 16z"/>',
    '<path d="M0 0h68v10H0zM0 19h54v10H0zM0 39h42v10H0zM0 58h30v10H0z"/>',
    '<path d="M34 0 68 34 34 68 0 34zm0 15L15 34l19 19 19-19z" fill-rule="evenodd"/><circle cx="34" cy="34" r="6"/>',
)


def render(w, h, body):
    markup = f'<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}">{body}</svg>'
    return Image.open(BytesIO(cairosvg.svg2png(bytestring=markup.encode()))).convert("RGBA")


def gradient(name, light, base, shade):
    return f"""<linearGradient id="{name}" x2=".86" y2="1">
    <stop stop-color="{shade}"/><stop offset=".24" stop-color="{light}"/>
    <stop offset=".55" stop-color="{base}"/><stop offset=".88" stop-color="{shade}"/>
    <stop offset="1" stop-color="{base}"/></linearGradient>"""


def apparel(cap, index, stone):
    light, base, shade = ("#D8D5CD", "#BCB8AE", "#827F77") if stone else ("#535353", "#303131", "#1E1F1F")
    accent = ("#003566", "#001D3D", "#000814", "#003566", "#001D3D", "#000814")[index] if stone else (
        "#E8E3D5", "#FFC300", "#DDD8CD", "#FFD60A", "#E4E0D8", "#E6E2D7"
    )[index]
    shape = CROWN if cap else TEE
    defs = gradient("cloth", light, base, shade) + gradient("edge", base, shade, light)
    defs += f'<clipPath id="outline"><path d="{shape}"/></clipPath>'
    if cap:
        details = f"""
        <path d="{BILL}" transform="translate(0 5)" fill="{shade}"/>
        <path d="{BILL}" fill="url(#edge)" stroke="{shade}" stroke-width="1.4"/>
        <path d="M254 408Q262 235 261 119M86 359Q122 220 218 139M386 390Q418 277 366 148M253 409Q332 348 460 335" fill="none" stroke="{shade}" stroke-width="2.1"/>
        <path d="M257 408Q267 234 265 122M93 359Q122 228 219 145M272 422Q439 433 537 401M291 433Q438 452 550 412M313 446Q452 462 559 424" fill="none" stroke="{light}" stroke-width="1.35" opacity=".52" stroke-dasharray="2.7 1.5"/>
        <ellipse cx="262" cy="124" rx="14" ry="6" fill="{base}" stroke="{shade}" stroke-width="1"/>
        <circle cx="171" cy="220" r="3" fill="{shade}"/><circle cx="338" cy="215" r="3" fill="{shade}"/>
        """
        position = "translate(288 249) scale(.64 1)"
    else:
        details = f"""
        <g clip-path="url(#outline)" fill="none">
          <path d="M169 113Q198 151 177 254M442 108Q412 147 430 254M156 249Q122 265 88 280m351-29q37 14 80 32M169 496Q319 510 435 497" stroke="{shade}" stroke-width="2.2"/>
          <path d="M167 118Q192 153 173 250M447 115Q423 161 435 252M174 500Q309 514 434 500M86 276l74-28m281 0 78 31" stroke="{light}" stroke-width="1.4" stroke-dasharray="2 2" opacity=".53"/>
          <path d="M176 262Q185 308 205 338M432 256Q413 270 402 331M190 426Q195 461 183 505M418 403Q405 450 416 495M219 109Q202 140 206 161M398 106Q420 135 414 151" stroke="{shade}" stroke-width="5" opacity=".28"/>
          <path d="M179 263Q190 309 209 334M429 258Q411 280 408 329M195 425Q199 466 187 503M414 407Q402 452 410 496" stroke="{light}" stroke-width="3" opacity=".24"/>
        </g>
        <path d="M250 82Q301 102 351 82Q341 134 301 143Q262 139 250 82Z" fill="{shade}" stroke="{shade}" stroke-width="2"/>
        <path d="M251 84Q264 139 300 144Q337 141 350 85M256 88Q267 133 300 139Q333 135 345 88" fill="none" stroke="{base}" stroke-width="3"/>
        <path d="M281 99q18 4 36 0l-2 11q-13 4-31 0Z" fill="#E2DFD8"/>
        <path d="M288 104h21m-19 2h14" stroke="#515151" stroke-width=".55"/>
        """
        position = "translate(304 203) scale(1.16)"
    layer = render(600, 590, f"""<defs>{defs}</defs>
        <path d="{shape}" fill="url(#cloth)" stroke="{shade}" stroke-width="1.4"/>
        {details}<g transform="{position}" fill="{accent}" opacity=".93">{PRINTS[index]}</g>""")
    alpha = layer.getchannel("A")
    rng = Random(17)
    grain = Image.frombytes("L", layer.size, rng.randbytes(layer.width * layer.height))
    fabric = ImageChops.soft_light(layer.convert("RGB"), grain.point(lambda n: 102 + n // 5).convert("RGB"))
    fabric.putalpha(alpha)
    return fabric


def scene(w, h, pieces, backdrop):
    base = render(w, h, f"""<defs><linearGradient id="paper" x2="1" y2="1">
        <stop stop-color="{backdrop[0]}"/><stop offset="1" stop-color="{backdrop[1]}"/>
        </linearGradient></defs><rect width="{w}" height="{h}" fill="url(#paper)"/>""")
    for layer, (x, y), scale, angle in pieces:
        art = layer.resize((round(layer.width * scale), round(layer.height * scale)), Image.Resampling.LANCZOS)
        art = art.rotate(angle, Image.Resampling.BICUBIC, expand=True)
        x, y = x - art.width // 2, y - art.height // 2
        for dx, dy, blur, strength in ((11, 18, 24, .26), (3, 5, 6, .34)):
            mask = Image.new("L", (w, h))
            mask.paste(art.getchannel("A"), (x + dx, y + dy))
            shadow = Image.new("RGBA", (w, h), "#292929")
            shadow.putalpha(mask.filter(ImageFilter.GaussianBlur(blur)).point(lambda n: round(n * strength)))
            base.alpha_composite(shadow)
        base.alpha_composite(art, (x, y))
    noise = Image.frombytes("L", (w, h), Random(26).randbytes(w * h))
    return ImageChops.soft_light(base.convert("RGB"), noise.point(lambda n: 120 + n // 16).convert("RGB"))


def save(name, image):
    image.save(OUT / f"{name}.webp", quality=88, method=5)


def main():
    OUT.mkdir(exist_ok=True)
    neutral = ("#F0EEEA", "#E2E1DD")
    for cap, names in ((False, TEES), (True, CAPS)):
        for index, slug in enumerate(names):
            for finish, stone in (("ink", False), ("stone", True)):
                piece = apparel(cap, index, stone)
                save(f"{slug}-{finish}", scene(900, 1000, [(piece, (450, 477 if cap else 498), 1.25 if cap else 1.23, 0)], neutral))
                if not stone:
                    detail = (264, 540) if cap else (220, 625)
                    save(f"{slug}-detail", scene(900, 1000, [(piece, detail, 3.25, -13 if cap else 13)], ("#EBE9E5", "#DCDAD6")))
    save("collection-tees", scene(1000, 680, [(apparel(False, 2, True), (510, 330), 1.14, 10)], neutral))
    save("collection-hats", scene(1000, 680, [(apparel(True, 3, False), (500, 317), 1.18, -9)], neutral))
    save("hero", scene(1440, 1130, [
        (apparel(False, 0, False), (590, 514), 1.79, 15),
        (apparel(True, 2, True), (1060, 836), 1.06, -12),
    ], ("#F2F0EB", "#DBD9D4")))
    save("lookbook", scene(1440, 956, [
        (apparel(False, 1, True), (530, 456), 1.66, -10),
        (apparel(True, 1, False), (1080, 615), 1.01, 14),
    ], ("#F4F3F0", "#DEDDD8")))
    studies = render(380, 320, """
        <rect x="26" y="20" width="328" height="280" fill="#fff"/>
        <path d="M60 64H320M60 160H320M60 256H320M92 42V278M190 42V278M288 42V278" stroke="#d8d7d3"/>
        <path d="M112 88h65v60h-65zm93 72h65v60h-65z" fill="#252525"/>
        <path d="M207 90h61v56h-61zm-93 72h61v56h-61z" fill="none" stroke="#252525" stroke-width="3"/>""")
    save("process-layout", scene(900, 670, [(studies, (450, 335), 1.64, -10)], neutral))
    save("process-print", scene(900, 670, [(apparel(False, 1, True), (438, 392), 2.65, 15)], neutral))
    save("process-stitch", scene(900, 670, [(apparel(True, 1, False), (470, 335), 2.59, -13)], neutral))
    render(330, 74, """<text x="0" y="57" font-family="Liberation Sans" font-size="59"
        font-weight="bold" letter-spacing="-3" fill="#1B1B1B">FORM<tspan fill="#003566"> / </tspan>EDIT</text>
        <circle cx="319" cy="48" r="5" fill="#FFC300"/>""").save(OUT / "wordmark.png", optimize=True)


if __name__ == "__main__":
    main()
