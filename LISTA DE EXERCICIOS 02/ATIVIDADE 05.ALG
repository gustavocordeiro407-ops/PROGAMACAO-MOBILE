algoritmo "ATIVIDADE 05"
var
   n, i: inteiro
   fat: real // Fatorial cresce rápido, real evita overflow
inicio
   repita
      escreva("Digite número positivo: ")
      leia(n)
   ate n >= 0

   fat <- 1
   para i de 1 ate n faca
      fat <- fat * i
   fimpara

   escreva("Fatorial de ", n, " é: ", fat)
fimalgoritmo