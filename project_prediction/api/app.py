from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib

app = Flask(__name__)
CORS(app)  # ✅ This line enables CORS for all routes

# Load model and vectorizer
model = joblib.load('model.pkl')
vectorizer = joblib.load('vectorizer.pkl')

@app.route('/predict', methods=['POST'])
def predict():
    data = request.get_json()
    title = data['title']
    description = data['description']
    combined_text = title + " " + description
    features = vectorizer.transform([combined_text])
    prediction = model.predict_proba(features)[0][1] * 100
    return jsonify({'prediction': round(prediction, 2)})

if __name__ == '__main__':
    app.run(port=5001)
