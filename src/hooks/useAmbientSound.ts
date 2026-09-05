import { useCallback, useEffect, useState } from "react";

/**
 * Ambient "room tone" synthesized with the Web Audio API.
 *  - a soft low drone (two detuned sines with a slow breathing LFO)
 *  - warm filtered brown noise (air / distant hum)
 *  - randomised candle crackle (short band-passed noise bursts)
 * No audio files needed — starts only after a user gesture (browser policy).
 */
class AmbientEngine {
  private ctx: AudioContext | null = null;
  private master: GainNode | null = null;
  private crackleTimer: number | null = null;
  private noiseBuffer: AudioBuffer | null = null;
  running = false;

  private ensure() {
    if (this.ctx) return this.ctx;
    const Ctx = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
    this.ctx = new Ctx();
    this.master = this.ctx.createGain();
    this.master.gain.value = 0;
    this.master.connect(this.ctx.destination);
    return this.ctx;
  }

  private getNoise(ctx: AudioContext) {
    if (this.noiseBuffer) return this.noiseBuffer;
    const len = ctx.sampleRate * 3;
    const buf = ctx.createBuffer(1, len, ctx.sampleRate);
    const data = buf.getChannelData(0);
    let last = 0;
    for (let i = 0; i < len; i++) {
      const white = Math.random() * 2 - 1;
      last = (last + 0.02 * white) / 1.02; // brown noise
      data[i] = last * 3.5;
    }
    this.noiseBuffer = buf;
    return buf;
  }

  start() {
    const ctx = this.ensure();
    if (!this.master) return;
    if (ctx.state === "suspended") void ctx.resume();
    if (this.running) return;
    this.running = true;
    const now = ctx.currentTime;

    // --- Drone -------------------------------------------------------
    const droneGain = ctx.createGain();
    droneGain.gain.value = 0.05;
    const lfo = ctx.createOscillator();
    lfo.frequency.value = 0.08;
    const lfoGain = ctx.createGain();
    lfoGain.gain.value = 0.02;
    lfo.connect(lfoGain).connect(droneGain.gain);
    lfo.start(now);
    [55, 82.41, 110.3].forEach((f, i) => {
      const o = ctx.createOscillator();
      o.type = "sine";
      o.frequency.value = f;
      o.detune.value = i * 4;
      const g = ctx.createGain();
      g.gain.value = i === 0 ? 1 : 0.35;
      o.connect(g).connect(droneGain);
      o.start(now);
    });
    droneGain.connect(this.master);

    // --- Air / hum (filtered brown noise) --------------------------------
    const noise = ctx.createBufferSource();
    noise.buffer = this.getNoise(ctx);
    noise.loop = true;
    const lp = ctx.createBiquadFilter();
    lp.type = "lowpass";
    lp.frequency.value = 420;
    const airGain = ctx.createGain();
    airGain.gain.value = 0.16;
    noise.connect(lp).connect(airGain).connect(this.master);
    noise.start(now);

    // --- Candle crackle ---------------------------------------------------
    const crackle = () => {
      if (!this.ctx || !this.master || !this.running) return;
      const c = this.ctx;
      const src = c.createBufferSource();
      src.buffer = this.getNoise(c);
      src.playbackRate.value = 2 + Math.random() * 3;
      const bp = c.createBiquadFilter();
      bp.type = "bandpass";
      bp.frequency.value = 1500 + Math.random() * 2500;
      bp.Q.value = 1.2;
      const g = c.createGain();
      const t = c.currentTime;
      const peak = 0.015 + Math.random() * 0.05;
      g.gain.setValueAtTime(0, t);
      g.gain.linearRampToValueAtTime(peak, t + 0.004);
      g.gain.exponentialRampToValueAtTime(0.0005, t + 0.03 + Math.random() * 0.05);
      src.connect(bp).connect(g).connect(this.master);
      src.start(t);
      src.stop(t + 0.15);
      this.crackleTimer = window.setTimeout(crackle, 60 + Math.random() * 520);
    };
    crackle();

    // Fade in
    this.master.gain.cancelScheduledValues(now);
    this.master.gain.setValueAtTime(0, now);
    this.master.gain.linearRampToValueAtTime(0.9, now + 2.5);
  }

  stop() {
    if (!this.ctx || !this.master) return;
    const now = this.ctx.currentTime;
    this.master.gain.cancelScheduledValues(now);
    this.master.gain.setValueAtTime(this.master.gain.value, now);
    this.master.gain.linearRampToValueAtTime(0, now + 1.2);
    this.running = false;
    if (this.crackleTimer) window.clearTimeout(this.crackleTimer);
    window.setTimeout(() => {
      if (!this.running && this.ctx) {
        void this.ctx.close();
        this.ctx = null;
        this.master = null;
      }
    }, 1400);
  }

  /** A soft, low swell when the doors open. */
  doorSwell() {
    if (!this.ctx || !this.master || !this.running) return;
    const c = this.ctx;
    const t = c.currentTime;
    const src = c.createBufferSource();
    src.buffer = this.getNoise(c);
    const lp = c.createBiquadFilter();
    lp.type = "lowpass";
    lp.frequency.setValueAtTime(120, t);
    lp.frequency.exponentialRampToValueAtTime(900, t + 1.6);
    lp.frequency.exponentialRampToValueAtTime(140, t + 3.2);
    const g = c.createGain();
    g.gain.setValueAtTime(0, t);
    g.gain.linearRampToValueAtTime(0.5, t + 1.4);
    g.gain.linearRampToValueAtTime(0, t + 3.4);
    src.connect(lp).connect(g).connect(this.master);
    src.start(t);
    src.stop(t + 3.6);
    // a low bell-like tone
    const o = c.createOscillator();
    o.type = "sine";
    o.frequency.value = 164.8;
    const og = c.createGain();
    og.gain.setValueAtTime(0, t + 0.6);
    og.gain.linearRampToValueAtTime(0.08, t + 1.2);
    og.gain.exponentialRampToValueAtTime(0.0005, t + 5);
    o.connect(og).connect(this.master);
    o.start(t + 0.6);
    o.stop(t + 5.2);
  }
}

const engine = new AmbientEngine();

export function useAmbientSound() {
  const [on, setOn] = useState(false);

  const toggle = useCallback(() => {
    setOn((prev) => {
      const next = !prev;
      if (next) engine.start();
      else engine.stop();
      return next;
    });
  }, []);

  const doorSwell = useCallback(() => engine.doorSwell(), []);

  // Stop if the tab is closed/unmounted
  useEffect(() => () => engine.stop(), []);

  return { on, toggle, doorSwell };
}
