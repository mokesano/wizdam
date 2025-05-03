import React, { createContext, useState, useContext } from 'react';

// Membuat context
const AppContext = createContext();

// Custom hook untuk menggunakan context
export const useAppContext = () => useContext(AppContext);

// Provider component
export const AppProvider = ({ children }) => {
  // State global
  const [selectedField, setSelectedField] = useState('all');
  const [timeRange, setTimeRange] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Value yang akan dibagikan ke seluruh aplikasi
  const value = {
    selectedField,
    setSelectedField,
    timeRange,
    setTimeRange,
    searchQuery,
    setSearchQuery
  };

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
};
