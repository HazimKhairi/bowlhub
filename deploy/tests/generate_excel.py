#!/usr/bin/env python3
"""Generate test Excel files for participant + score import."""

from openpyxl import Workbook
from pathlib import Path

OUT = Path(__file__).parent

# 1. Individual import test data
individual_wb = Workbook()
ws = individual_wb.active
ws.title = "Individual"
ws.append(["no_kp", "nama_penuh", "no_telefon", "nickname", "nama_pasukan", "jantina"])
individual_data = [
    ["900101015551", "Ahmad Imran",      "0123456701", "ahmad",   "Strikers",   "lelaki"],
    ["920202025552", "Siti Aishah",      "0123456702", "siti",    "Pin Queens", "wanita"],
    ["930303035553", "Ali Hassan",       "0123456703", "ali",     "Kingpins",   "lelaki"],
    ["940404045554", "Nurul Aina",       "0123456704", "nurul",   "Spare Belles","wanita"],
    ["950505055555", "Faiz Iskandar",    "0123456705", "faiz",    "Strike Force","lelaki"],
    ["960606065556", "Mira Hanis",       "0123456706", "mira",    "Lane Ladies","wanita"],
]
for row in individual_data:
    ws.append(row)
individual_wb.save(OUT / "test-individual.xlsx")
print(f"✅ {OUT / 'test-individual.xlsx'} ({len(individual_data)} participants)")

# 2. Score import test data (by nickname)
score_wb = Workbook()
ws = score_wb.active
ws.title = "Scores"
ws.append(["nickname", "g1", "g2", "g3", "g4", "g5"])
score_data = [
    ["ahmad",   180, 210, 195, 200, 175],   # match
    ["siti",    155, 165, 175, 160, 150],   # match
    ["ali",     220, 215, 205, 230, 195],   # match
    ["nurul",   145, 160, 170, 155, 165],   # match
    ["faiz",    190, 185, 200, 210, 195],   # match
    ["mira",    140, 150, 160, 155, 145],   # match
    ["unknown_nick", 200, 195, 185, 190, 200],  # unmatched (test)
    ["typo_nick",    175, 180, 170, 165, 160],  # unmatched (test)
]
for row in score_data:
    ws.append(row)
score_wb.save(OUT / "test-scores.xlsx")
print(f"✅ {OUT / 'test-scores.xlsx'} ({len(score_data)} score rows, 2 unmatched)")
