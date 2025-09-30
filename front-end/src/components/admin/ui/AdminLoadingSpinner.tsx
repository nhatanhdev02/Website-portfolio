import React from 'react';

export const AdminLoadingSpinner: React.FC = () => {
  return (
    <div className="flex items-center justify-center min-h-[400px]">
      <div className="flex flex-col items-center">
        <div className="grid grid-cols-2 gap-0.5 w-8 h-8">
          <div 
            className="w-3.5 h-3.5 bg-green-400"
            style={{
              animation: 'pixelPulse 1.2s ease-in-out infinite',
              animationDelay: '0s'
            }}
          ></div>
          <div 
            className="w-3.5 h-3.5 bg-green-400"
            style={{
              animation: 'pixelPulse 1.2s ease-in-out infinite',
              animationDelay: '0.2s'
            }}
          ></div>
          <div 
            className="w-3.5 h-3.5 bg-green-400"
            style={{
              animation: 'pixelPulse 1.2s ease-in-out infinite',
              animationDelay: '0.4s'
            }}
          ></div>
          <div 
            className="w-3.5 h-3.5 bg-green-400"
            style={{
              animation: 'pixelPulse 1.2s ease-in-out infinite',
              animationDelay: '0.6s'
            }}
          ></div>
        </div>
        <p className="mt-4 text-pixel-primary font-pixel text-sm">Loading...</p>
      </div>
      
      <style>{`
        @keyframes pixelPulse {
          0%, 80%, 100% {
            opacity: 0.3;
            transform: scale(0.8);
          }
          40% {
            opacity: 1;
            transform: scale(1);
          }
        }
      `}</style>
    </div>
  );
};