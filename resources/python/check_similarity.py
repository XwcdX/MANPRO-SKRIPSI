from flask import Flask, request, jsonify
from sentence_transformers import SentenceTransformer, util
import numpy as np

app = Flask(__name__)
# Load model sekali saja saat start
model = SentenceTransformer("sentence-transformers/all-MiniLM-L6-v2")

@app.route("/vectorize", methods=["POST"])
def vectorize():
    """Mengubah text menjadi vector"""
    data = request.get_json()
    text = data.get("text")
    
    if not text:
        return jsonify({"error": "Text is required"}), 400

    # Encode menjadi list of floats (supaya bisa jadi JSON)
    vector = model.encode(text).tolist()
    return jsonify({"vector": vector})

@app.route("/similarity-search", methods=["POST"])
def similarity_search():
    """Membandingkan 1 vector dengan banyak vector kandidat"""
    data = request.get_json()
    source_vector = data.get("source_vector")       # List [0.1, 0.2, ...]
    candidate_vectors = data.get("candidate_vectors") # List of Lists [[...], [...]]
    candidate_ids = data.get("candidate_ids")       # List of IDs [1, 2, 3] untuk tracking

    if not source_vector or not candidate_vectors:
        return jsonify({"error": "Vectors required"}), 400
    
    # Kita pakai numpy/pytorch tensor langsung dari list angka
    import torch
    src_tensor = torch.tensor(source_vector)
    candidates_tensor = torch.tensor(candidate_vectors)

    # Hitung Cosine Similarity sekaligus (Matrix Operation - Sangat Cepat)
    # Outputnya adalah array score kemiripan
    cosine_scores = util.cos_sim(src_tensor, candidates_tensor)[0]

    # Cari nilai tertinggi
    best_score = -1.0
    best_idx = -1

    for i, score in enumerate(cosine_scores):
        if score > best_score:
            best_score = float(score)
            best_idx = i
            
    THRESHOLD = 0.70 # 70%

    if best_score >= THRESHOLD:
        return jsonify({
            "is_similar": True,
            "score": best_score,
            "matched_id": candidate_ids[best_idx]
        })
    else:
        return jsonify({
            "is_similar": False,
            "score": best_score
        })

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001)