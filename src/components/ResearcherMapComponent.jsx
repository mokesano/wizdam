import React from 'react';
import { Link } from 'react-router-dom';
import { useAppContext } from '../context/AppContext';

const ResearcherMapComponent = () => {
  // Menggunakan state global
  const { selectedField } = useAppContext();
  
  // Data untuk peta
  const provinceResearcherData = [
    { province: 'DKI Jakarta', researchers: 2584, avgImpact: 82.4, institutions: 58, topField: 'Teknologi Informasi', lat: -6.2, lng: 106.8 },
    // ... data lainnya
  ];
  
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-blue-700 text-white p-4 shadow-md">
        <div className="container mx-auto flex justify-between items-center">
          <Link to="/" className="flex items-center">
            <img src="/logo.png" alt="Wizdom Indonesia Logo" className="w-10 h-10 mr-3" />
            <h1 className="text-xl font-bold">Wizdom Indonesia</h1>
          </Link>
        </div>
      </header>
      
      {/* Navigation */}
      <nav className="bg-white shadow-sm">
        <div className="container mx-auto">
          <ul className="flex flex-wrap space-x-1 md:space-x-8 p-4">
            <li>
              <Link 
                to="/"
                className="font-medium text-gray-600 hover:text-blue-700"
              >
                Dashboard
              </Link>
            </li>
            <li>
              <Link 
                to="/article-impact"
                className="font-medium text-gray-600 hover:text-blue-700"
              >
                Dampak Artikel
              </Link>
            </li>
            <li>
              <Link 
                to="/researchers"
                className="font-medium text-gray-600 hover:text-blue-700"
              >
                Peneliti Terkemuka
              </Link>
            </li>
            <li>
              <Link 
                to="/researcher-map"
                className="font-medium text-blue-700 border-b-2 border-blue-700"
              >
                Peta Distribusi
              </Link>
            </li>
            <li>
              <Link 
                to="/trends"
                className="font-medium text-gray-600 hover:text-blue-700"
              >
                Tren & Analisis
              </Link>
            </li>
          </ul>
        </div>
      </nav>
      
      {/* Main Content - placeholder untuk peta */}
      <main className="container mx-auto p-4 md:p-6">
        <h2 className="text-lg font-semibold mb-4">Peta Distribusi Peneliti di Indonesia</h2>
        
        {/* Placeholder untuk peta */}
        <div className="bg-white p-4 rounded-lg shadow h-96 flex items-center justify-center">
          <p className="text-center text-gray-600">
            Peta distribusi peneliti akan ditampilkan di sini.<br />
            Data sudah tersedia untuk {provinceResearcherData.length} provinsi.
          </p>
        </div>
      </main>
      
      {/* Footer */}
      <footer className="bg-gray-100 p-4 mt-6">
        <div className="container mx-auto text-center text-sm text-gray-600">
          <p>© 2025 Wizdom Indonesia - Platform Analisis Dampak Penelitian Indonesia</p>
          <p className="mt-1">Versi 1.0 - Dikembangkan oleh Tim Wizdom Indonesia</p>
        </div>
      </footer>
    </div>
  );
};

export default ResearcherMapComponent;
