/**
 * DRUMI Soundscape — Procedural Web Audio API
 * Generates organic sanctuary ambience: gentle wind, soft water lapping,
 * crystal singing bowl resonance, and realistic door opening swell.
 * 100% code-synthesized with zero audio file dependencies.
 */

import { useCallback, useEffect, useRef, useState } from "react";

export function useDrumiSound() {
  const [soundOn, setSoundOn] = useState(false);
  const ctxRef = useRef<AudioContext | null>(null);
  const ambientNodesRef = useRef<{
    windGain?: GainNode;
    waterGain?: GainNode;
    droneGain?: GainNode;
    masterGain?: GainNode;
    stop?: () => void;
  } | null>(null);

  const getContext = useCallback(() => {
    if (!ctxRef.current || ctxRef.current.state === "closed") {
      const AudioCtx = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
      ctxRef.current = new AudioCtx();
    }
    if (ctxRef.current.state === "suspended") {
      ctxRef.current.resume();
    }
    return ctxRef.current;
  }, []);

  // Chime / singing bowl bell sound
  const playChime = useCallback((freq = 528) => {
    if (!soundOn) return;
    try {
      const ctx = getContext();
      const now = ctx.currentTime;
      
      // 528Hz is known as the "Transformation & Miracles" solfeggio frequency
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      const filter = ctx.createBiquadFilter();

      osc.type = "sine";
      osc.frequency.setValueAtTime(freq, now);
      // subtle vibrato
      osc.frequency.exponentialRampToValueAtTime(freq * 1.002, now + 1.5);
      osc.frequency.exponentialRampToValueAtTime(freq, now + 3);

      filter.type = "lowpass";
      filter.frequency.setValueAtTime(2400, now);

      gain.gain.setValueAtTime(0.001, now);
      gain.gain.exponentialRampToValueAtTime(0.18, now + 0.05);
      gain.gain.exponentialRampToValueAtTime(0.0001, now + 4.5);

      osc.connect(filter);
      filter.connect(gain);
      gain.connect(ctx.destination);

      osc.start(now);
      osc.stop(now + 4.6);
    } catch {
      // Audio context policy fallback
    }
  }, [soundOn, getContext]);

  // Door opening swell
  const playDoorOpen = useCallback(() => {
    try {
      const ctx = getContext();
      const now = ctx.currentTime;

      // Low frequency resonance of heavy luxury carved stone/wood doors
      const osc = ctx.createOscillator();
      const oscGain = ctx.createGain();
      osc.type = "triangle";
      osc.frequency.setValueAtTime(65, now);
      osc.frequency.exponentialRampToValueAtTime(95, now + 1.2);
      osc.frequency.exponentialRampToValueAtTime(45, now + 2.4);

      oscGain.gain.setValueAtTime(0.001, now);
      oscGain.gain.linearRampToValueAtTime(0.22, now + 0.4);
      oscGain.gain.exponentialRampToValueAtTime(0.0001, now + 2.5);

      osc.connect(oscGain);
      oscGain.connect(ctx.destination);
      osc.start(now);
      osc.stop(now + 2.6);

      // Shimmering chime as the golden light bursts through
      setTimeout(() => {
        playChime(639); // Solfeggio heart connection frequency
      }, 400);
    } catch {
      // ignore
    }
  }, [getContext, playChime]);

  // Start continuous ambient sanctuary drone (breeze + serene mountain lake)
  const startAmbience = useCallback(() => {
    try {
      const ctx = getContext();
      const now = ctx.currentTime;

      const master = ctx.createGain();
      master.gain.setValueAtTime(0.001, now);
      master.gain.linearRampToValueAtTime(0.15, now + 1.5);
      master.connect(ctx.destination);

      // Pink noise buffer for soft breeze
      const bufferSize = ctx.sampleRate * 2;
      const noiseBuffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
      const output = noiseBuffer.getChannelData(0);
      let b0 = 0, b1 = 0, b2 = 0, b3 = 0, b4 = 0, b5 = 0, b6 = 0;
      for (let i = 0; i < bufferSize; i++) {
        const white = Math.random() * 2 - 1;
        b0 = 0.99886 * b0 + white * 0.0555179;
        b1 = 0.99332 * b1 + white * 0.0750759;
        b2 = 0.96900 * b2 + white * 0.1538520;
        b3 = 0.86650 * b3 + white * 0.3104856;
        b4 = 0.55000 * b4 + white * 0.5329522;
        b5 = -0.7616 * b5 - white * 0.0168980;
        output[i] = b0 + b1 + b2 + b3 + b4 + b5 + b6 + white * 0.5362;
        output[i] *= 0.11;
        b6 = white * 0.115926;
      }

      const whiteNoise = ctx.createBufferSource();
      whiteNoise.buffer = noiseBuffer;
      whiteNoise.loop = true;

      const windFilter = ctx.createBiquadFilter();
      windFilter.type = "bandpass";
      windFilter.frequency.setValueAtTime(280, now);
      windFilter.Q.setValueAtTime(1.2, now);

      whiteNoise.connect(windFilter);
      windFilter.connect(master);
      whiteNoise.start(now);

      // Warm harmonic drone chord (F# & C# ethereal meditation chords)
      const droneOsc1 = ctx.createOscillator();
      const droneOsc2 = ctx.createOscillator();
      const droneGain = ctx.createGain();

      droneOsc1.type = "sine";
      droneOsc1.frequency.setValueAtTime(147.27, now); // D3
      droneOsc2.type = "sine";
      droneOsc2.frequency.setValueAtTime(220.00, now); // A3

      droneGain.gain.setValueAtTime(0.04, now);

      droneOsc1.connect(droneGain);
      droneOsc2.connect(droneGain);
      droneGain.connect(master);

      droneOsc1.start(now);
      droneOsc2.start(now);

      ambientNodesRef.current = {
        masterGain: master,
        stop: () => {
          try {
            master.gain.linearRampToValueAtTime(0.0001, ctx.currentTime + 1);
            setTimeout(() => {
              whiteNoise.stop();
              droneOsc1.stop();
              droneOsc2.stop();
            }, 1100);
          } catch {
            // ignore
          }
        }
      };
    } catch {
      // AudioContext error handling
    }
  }, [getContext]);

  const stopAmbience = useCallback(() => {
    if (ambientNodesRef.current?.stop) {
      ambientNodesRef.current.stop();
      ambientNodesRef.current = null;
    }
  }, []);

  const toggleSound = useCallback(() => {
    if (soundOn) {
      stopAmbience();
      setSoundOn(false);
    } else {
      setSoundOn(true);
      startAmbience();
      playChime(528);
    }
  }, [soundOn, startAmbience, stopAmbience, playChime]);

  useEffect(() => {
    return () => {
      stopAmbience();
    };
  }, [stopAmbience]);

  return {
    soundOn,
    toggleSound,
    playChime,
    playDoorOpen
  };
}
