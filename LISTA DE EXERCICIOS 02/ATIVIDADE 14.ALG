algoritmo "ATIVIDADE 14"
var
   voto, c1, c2, c3, total: inteiro
inicio
   c1 <- 0
   c2 <- 0
   c3 <- 0
   total <- 0

   escreva("Voto (1, 2, 3 ou 0 sai): ")
   leia(voto)

   enquanto voto <> 0 faca
      se voto = 1 entao
         c1 <- c1 + 1
      senao
         se voto = 2 entao
            c2 <- c2 + 1
         senao
            se voto = 3 entao
               c3 <- c3 + 1
            fimse
         fimse
      fimse
      total <- total + 1
      escreva("Voto (1, 2, 3 ou 0 sai): ")
      leia(voto)
   fimenquanto

   se total > 0 entao
      escreval("Cand 1: ", c1, " (", (c1/total)*100, "%)")
      escreval("Cand 2: ", c2, " (", (c2/total)*100, "%)")
      escreval("Cand 3: ", c3, " (", (c3/total)*100, "%)")

      se (c1 > c2) e (c1 > c3) entao
         escreval("Vencedor: 1")
      senao
         se (c2 > c1) e (c2 > c3) entao
            escreval("Vencedor: 2")
         senao
            se (c3 > c1) e (c3 > c2) entao
               escreval("Vencedor: 3")
            senao
               escreval("Empate")
            fimse
         fimse
      fimse
   fimse
fimalgoritmo