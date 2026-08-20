from pathlib import Path

path = Path("mdo-supplier-sync/includes/class-mdo-auto-categorizer.php")
text = path.read_text(encoding="utf-8")

old_cheese = "\t\t\t$sets[] = array( 'queso', 'manchego', 'semicurado', 'curado', 'oveja', 'cabra' );"
new_cheese = "\t\t\t$sets[] = array( 'queso', 'quesos', 'manchego', 'semicurado' );"
if old_cheese in text:
    text = text.replace(old_cheese, new_cheese, 1)
elif new_cheese not in text:
    raise SystemExit("Cheese keyword block not found")

old_score = """\t\t\tif ( self::contains_phrase( $title, $keyword ) ) {\n\t\t\t\t$score += 7.0;\n\t\t\t}\n\t\t\tif ( self::contains_phrase( $url, $keyword ) ) {\n\t\t\t\t$score += 5.0;\n\t\t\t}\n\t\t\tif ( self::contains_phrase( $description, $keyword ) ) {\n\t\t\t\t$score += 2.0;\n\t\t\t}"""
new_score = """\t\t\t$phrase_bonus = str_contains( $keyword, ' ' ) ? 2.0 : 1.0;\n\t\t\tif ( self::contains_phrase( $title, $keyword ) ) {\n\t\t\t\t$score += 7.0 + $phrase_bonus;\n\t\t\t}\n\t\t\tif ( self::contains_phrase( $url, $keyword ) ) {\n\t\t\t\t$score += 5.0 + ( $phrase_bonus / 2 );\n\t\t\t}\n\t\t\tif ( self::contains_phrase( $description, $keyword ) ) {\n\t\t\t\t$score += 2.0;\n\t\t\t}"""
if old_score in text:
    text = text.replace(old_score, new_score, 1)
elif new_score not in text:
    raise SystemExit("Keyword scoring block not found")

path.write_text(text, encoding="utf-8")
print("auto_categorizer_tuning_ok")
