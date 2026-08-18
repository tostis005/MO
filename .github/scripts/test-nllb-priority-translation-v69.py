import json, re, torch
from transformers import AutoTokenizer, AutoModelForSeq2SeqLM

MODEL='facebook/nllb-200-distilled-600M'
tok=AutoTokenizer.from_pretrained(MODEL,src_lang='spa_Latn',tgt_lang='eng_Latn')
model=AutoModelForSeq2SeqLM.from_pretrained(MODEL)
model.eval(); torch.set_num_threads(2)

samples=[
'Llévate 6 y paga 5: sobres de 100 grs. de jamón de bellota ibérico 50% cortado a cuchillo por profesional.',
'Llévate este fantástico Pack de Paleta de Cebo de Campo que incluye: 15 SOBRES (0,100 grs cada uno) + PUNTA + CODILLO a un precio único.',
'Caña de lomo de cebo de campo 50% raza ibérica',
'La Caña de Lomo de Cebo de Campo 50% raza ibérica procede de cerdos ibéricos criados en ganaderías de Ledesma y los Arribes del Duero.',
'El envío incluye el codillo, los tacos de jamón y todas las partes aprovechables y comestibles resultantes del corte envasadas al vacío.',
'Paleta de bellota ibérica 75% raza ibérica cortada a máquina',
'Ingredientes: cinta de lomo 100% ibérica, sal, pimentón, ajo y conservador (E250).',
'Información nutricional media por 100 g: Valor energético 315 kcal / 1317 kJ. Grasas totales 15 g, de las cuales saturadas 5 g. Hidratos de carbono 0 g. Proteínas 46 g. Sal 3,5 g.',
'Este pack contiene 2 sobres de 100 g de jamón de bellota 100% ibérico cortado a cuchillo y 2 sobres de 100 g de paleta de bellota 100% ibérica cortada a máquina.',
'Lomo procedente de un cerdo de pura raza ibérica, alimentado con bellota, hierba y otros recursos de la dehesa y criado en libertad, con la garantía de Hidalgo de la Jara.'
]

def translate(s):
    inputs=tok(s,return_tensors='pt',truncation=True,max_length=512)
    with torch.no_grad():
        out=model.generate(**inputs,forced_bos_token_id=tok.convert_tokens_to_ids('eng_Latn'),max_length=600,num_beams=4)
    return tok.batch_decode(out,skip_special_tokens=True)[0]

for i,s in enumerate(samples,1):
    print(f'--- SAMPLE {i} ---')
    print('ES:',s)
    print('EN:',translate(s))
