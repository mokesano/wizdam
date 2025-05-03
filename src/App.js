import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import WizdomIndonesiaDashboard from './components/WizdomIndonesiaDashboard';
import TopResearchersComponent from './components/TopResearchersComponent';
import TrendsAnalysisComponent from './components/TrendsAnalysisComponent';
import ArticleImpactComponent from './components/ArticleImpactComponent';
import ResearcherMapComponent from './components/ResearcherMapComponent';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<WizdomIndonesiaDashboard />} />
        <Route path="/article-impact" element={<ArticleImpactComponent />} />
        <Route path="/researchers" element={<TopResearchersComponent />} />
        <Route path="/researcher-map" element={<ResearcherMapComponent />} />
        <Route path="/trends" element={<TrendsAnalysisComponent />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
