import sys
import json
import numpy as np

def intelligence_artificielle_decision(offres):
    # Simulation d'un modèle prédictif : On normalise les données
    # Plus le score final est proche de 1, meilleure est l'offre selon l'IA
    
    resultats = []
    for offre in offres:
        prix = float(offre['prix'])
        delai = float(offre['delai'])
        
        # L'IA ici applique des poids appris (ex: pénalise fortement les délais longs)
        # Score = (1/Prix * 0.7) + (1/Delai * 0.3)
        score_ia = (100 / prix * 0.7) + (10 / delai * 0.3)
        
        offre['score_ia'] = round(score_ia, 4)
        resultats.append(offre)
    
    # On trie par le score IA le plus élevé
    return sorted(resultats, key=lambda x: x['score_ia'], reverse=True)

if __name__ == "__main__":
    # Récupère les données envoyées par Symfony
    input_data = json.loads(sys.stdin.read())
    output = intelligence_artificielle_decision(input_data)
    print(json.dumps(output))