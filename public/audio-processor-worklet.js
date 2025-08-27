class AudioProcessorWorklet extends AudioWorkletProcessor {
  constructor() {
    super();
    this.bufferSize = 1536; // 16kHz sample rate, ~96ms of audio
    this.buffer = new Float32Array(this.bufferSize);
    this.bufferIndex = 0;
  }

  process(inputs, outputs, parameters) {
    const input = inputs[0];
    const output = outputs[0];

    // If there's no input, just pass through
    if (!input || input.length === 0) {
      return true;
    }

    const inputChannel = input[0];
    
    // Copy input to output (pass-through)
    if (output && output.length > 0) {
      const outputChannel = output[0];
      for (let i = 0; i < inputChannel.length; i++) {
        outputChannel[i] = inputChannel[i];
      }
    }

    // Buffer audio for VAD processing
    for (let i = 0; i < inputChannel.length; i++) {
      this.buffer[this.bufferIndex] = inputChannel[i];
      this.bufferIndex++;

      // When buffer is full, send it for VAD processing
      if (this.bufferIndex >= this.bufferSize) {
        this.port.postMessage({
          type: 'audio',
          data: this.buffer.slice()
        });
        this.bufferIndex = 0;
      }
    }

    return true;
  }
}

registerProcessor('audio-processor-worklet', AudioProcessorWorklet);
