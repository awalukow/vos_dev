from pathlib import Path
from reportlab.pdfgen import canvas
from reportlab.lib.colors import HexColor, black, white
from reportlab.lib.utils import ImageReader
from reportlab.platypus import Paragraph
from reportlab.lib.styles import ParagraphStyle
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'output/pdf/Voice-of-Soul-Ticketing-Logo-Guidelines.pdf'
OUT.parent.mkdir(parents=True, exist_ok=True)
LOGO = str(ROOT / 'public/assets/images/vos-tickets-logo-v4.png')
pdfmetrics.registerFont(TTFont('Mitr', str(ROOT / 'public/assets/fonts/mitr/Mitr-SemiBold.ttf')))
C = canvas.Canvas(str(OUT), pagesize=(595.28,841.89))
C.setTitle('Voice of Soul Ticketing Logo Guidelines')
C.setAuthor('Voice of Soul Choir')
W,H=595.28,841.89
PLUM='#6a3d7b'; MAUVE='#b365a5'; PINK='#dfabd1'; PAPER='#f7f5fa'; INK='#251e36'
style=ParagraphStyle('body',fontName='Helvetica',fontSize=10.5,leading=15,textColor=HexColor(INK))

def text(s,x,y,width=491,size=10.5,color=INK,font='Helvetica'):
    st=ParagraphStyle('p',parent=style,fontSize=size,leading=size*1.43,textColor=HexColor(color),fontName=font)
    p=Paragraph(s,st); _,h=p.wrap(width,700); p.drawOn(C,x,H-y-h); return y+h
def label(s,x,y,size=10,color=INK,font='Helvetica'):
    C.setFont(font,size);C.setFillColor(HexColor(color)); C.drawString(x,H-y-size,s)
def box(x,y,w,h,color):
    C.setFillColor(HexColor(color)); C.rect(x,H-y-h,w,h,fill=1,stroke=0)
def logo(x,y,size):
    C.drawImage(LOGO,x,H-y-size,width=size,height=size,mask='auto')
def begin(n,title,desc):
    label('VOICE OF SOUL CHOIR   /   TICKETING IDENTITY',52,35,8,'#000000')
    label(title,52,76,25,'#000000','Helvetica-Bold')
    text(desc,52,119,size=11)
    label('Logo guidelines 1.0   |   8 October 2026',52,800,8,'#746d82')
    label(str(n).zfill(2),525,800,8,'#746d82')
def heading(s,y):label(s,52,y,14,'#000000','Helvetica-Bold')
def end():C.showPage()

begin(1,'Ticketing logo guidelines','Use this guide to apply the current Voice of Soul Choir ticketing logo consistently across the website, booking communications and concert materials.')
logo(167,181,260)
label('CURRENT LOGO   /   VERSION 4',202,455,8,'#746d82')
heading('The musical ribbon emblem',502)
text('Flowing ribbons suggest voices coming together. The central curve draws on the movement of a treble clef, while the lower convergence subtly recalls a V. These cues stay within the symbol rather than spelling out VOS.',52,529)
heading('Scope and status',606)
text('This is the logo selected for current ticketing use. Keep the artwork intact and use the supplied transparent PNG. This guide does not replace the existing choir identity on other properties.',52,633)
text('The spacing, size and application rules in this document are recommended working standards for this version.',52,701,size=10,color='#746d82')
end()

begin(2,'Color and backgrounds','Keep the plum, mauve and soft pink family consistent. Use a quiet, light background so the musical curve and open spaces remain clear.')
swatches=[('Plum',PLUM,'RGB 106 61 123'),('Mauve',MAUVE,'RGB 179 101 165'),('Soft pink',PINK,'RGB 223 171 209')]
for i,(name,hx,rgb) in enumerate(swatches):
    x=52+i*169
    box(x,189,153,86,hx)
    label(name,x,289,12,INK,'Helvetica-Bold');label(hx.upper(),x,312,10);label(rgb,x,332,9,'#746d82')
text('These are the target digital palette values. The current generated PNG contains tonal variations; do not recolor it to force exact matches. Use the values above for supporting brand elements and any future vector master.',52,373,size=10)
heading('Preferred background examples',459)
for x,bg,name in [(52,'#ffffff','White  #FFFFFF'),(306,PAPER,'Pale paper  #F7F5FA')]:
    box(x,490,237,157,bg)
    C.setStrokeColor(HexColor('#e6e0ed'));C.rect(x,H-490-157,237,157,fill=0,stroke=1)
    logo(x+58,504,126)
    label(name,x,659,10)
text('On a photograph or dark surface, place the full-color logo on a white or pale panel with the required clear space. Do not apply a white tint, dark-mode filter or automatic inversion. No reverse or monochrome version is defined yet.',52,706,size=10)
end()

begin(3,'Spacing size and wordmark','Leave room around the logo and preserve its square image proportions. Scale it uniformly and keep all curves visible.')
# Clear-space diagram uses the supplied square canvas so placement is reproducible.
x,y,s=95,206,156;gap=s/4
logo(x,y,s)
C.setStrokeColor(HexColor('#b8a4c3'));C.setDash(3,3)
C.rect(x-gap,H-y-s-gap,s+2*gap,s+2*gap,fill=0,stroke=1)
C.setDash();C.setStrokeColor(HexColor('#d9d9d9'));C.rect(x,H-y-s,s,s,fill=0,stroke=1)
label('x = one quarter of image width',61,422,9,'#746d82')
text('<b>Recommended clear space</b><br/>Use x on every side of the supplied square image. Keep text, borders and other graphics outside this zone. The transparent margin inside the file is additional.',318,198,width=225,size=10)
text('<b>Example</b><br/>At a 64 px image width, x is 16 px. At 76 px, x is 19 px. Increase the gap when the surrounding layout feels crowded.',318,318,width=225,size=10)
heading('Recommended sizes',471)
text('<b>Website emblem</b> 64 px minimum; 76 px preferred in a desktop header.<br/><b>Print emblem</b> Start at 20 mm wide and inspect a physical proof.<br/><b>Very small uses</b> Create a separately approved simplified icon for favicons or placements below 64 px. Do not squeeze this detailed mark down.',52,501,size=10.5)
heading('Website wordmark arrangement',598)
logo(52,637,76)
label('VOICE OF',149,642,17,PLUM,'Mitr');label('SOUL CHOIR',149,664,17,PLUM,'Mitr')
label('C O N C E R T   T I C K E T S',149,692,6.5,PLUM,'Helvetica-Bold')
text('Set the choir name in Mitr SemiBold. Use a smaller, spaced sans serif descriptor. Keep the name as live text on the web. The example shows a 76 px mark with at least 19 px separation.',52,743,size=9)
end()

begin(4,'Usage and asset handoff','Use the same source artwork across ticketing touchpoints. Consistency in proportion, spacing and color matters more than decorative effects.')
heading('Correct use',182)
text('Use the transparent logo on white or pale paper. Keep its orientation upright and preserve its original proportions. Pair it with the choir name when the audience may not recognize the emblem alone.',52,210)
text('For tickets and booking emails, keep the logo separate from QR codes and booking details. Protect the QR code quiet zone independently; the logo must never overlap it.',52,274)
heading('Avoid these changes',351)
text('Do not stretch, rotate, crop or rearrange the ribbons. Do not add literal VOS lettering inside the symbol, floating music notes, flames, outlines, drop shadows or new gradients. Do not recolor individual ribbons or use the pink alone as the entire mark.',52,379)
text('Avoid busy photographs, low-contrast backgrounds and animation that deforms the artwork. Do not use older concepts interchangeably with the selected version.',52,458)
heading('Source asset and production notes',528)
text('<b>File</b> vos-tickets-logo-v4.png<br/><b>Repository location</b> public/assets/images/<br/><b>Format</b> Transparent RGBA PNG, 1254 by 1254 pixels<br/><b>Current web route</b> /tickets/assets/vos-tickets-logo-v4.png',52,555,size=10)
text('The current asset is raster artwork. For large signage, embroidery, one-color printing or a tightly controlled print palette, commission a clean vector master and approve a proof before production. Do not label a PNG placed inside an SVG as a vector master.',52,641,size=10)
heading('Before publishing',720)
text('Check the current file, clear space, background contrast and legibility at the final size. For accessible web use, give a linked standalone logo a destination label; use empty image alt text when adjacent live text already names the choir.',52,746,size=9)
end()
C.save()
print(OUT)
