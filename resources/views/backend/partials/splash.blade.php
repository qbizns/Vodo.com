{{-- Splash Screen / Page Loader --}}
{{-- Shows for at least 3 seconds --}}
<div id="splashScreen" class="splash-screen">
    <style>
        .splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #1a1a1a;
            font-family: var(--font-family-base);
            overflow: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .splash-screen.splash-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .splash-panel {
            width: 500px;
            height: 340px;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .splash-gradient {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            opacity: 0.95;
        }

        .splash-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,.03) 10px, rgba(255,255,255,.03) 20px);
            pointer-events: none;
        }

        .splash-content {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 40px;
        }

        .splash-logo {
            width: 100px;
            height: 100px;
            background-color: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .splash-logo svg {
            width: 50px;
            height: 50px;
            color: #ffffff;
            stroke-width: 2.5;
        }

        .splash-title {
            font-size: 42px;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
            margin: 0 0 8px 0;
            letter-spacing: 1px;
        }

        .splash-subtitle {
            font-size: 16px;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 1px 10px rgba(0, 0, 0, 0.2);
            margin: 0;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .splash-progress-container {
            width: 100%;
            height: 3px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .splash-progress-bar {
            height: 100%;
            width: 0%;
            background-color: #ffffff;
            transition: width 0.1s linear;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        .splash-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .splash-footer span {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.7);
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: 0.5px;
        }
    </style>

    <div class="splash-panel">
        {{-- Gradient Background --}}
        <div class="splash-gradient"></div>
        
        {{-- Diagonal Pattern Overlay --}}
        <div class="splash-pattern"></div>
        
        {{-- Content --}}
        <div class="splash-content">
            {{-- Logo and Title Section --}}
            <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                {{-- Logo --}}
                <div class="splash-logo">
                    @if(isset($splashIcon))
                        {!! $splashIcon !!}
                    @else
                        {{-- Zap Icon SVG --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    @endif
                </div>
                
                {{-- App Name --}}
                <h1 class="splash-title">{{ $splashTitle ?? 'VODO' }}</h1>
                
                {{-- Subtitle --}}
                <p class="splash-subtitle">{{ $splashSubtitle ?? 'Platform' }}</p>
            </div>
            
            {{-- Bottom Section --}}
            <div style="margin-top: auto;">
                {{-- Progress Bar --}}
                <div class="splash-progress-container">
                    <div class="splash-progress-bar" id="splashProgressBar"></div>
                </div>
                
                {{-- Version and Copyright --}}
                <div class="splash-footer">
                    <span>{{ $splashVersion ?? 'Version 1.0.0' }}</span>
                    <span>{{ $splashCopyright ?? '© ' . date('Y') . ' VODO Systems' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
